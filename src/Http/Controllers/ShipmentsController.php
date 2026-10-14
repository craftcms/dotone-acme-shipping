<?php

declare(strict_types=1);

namespace Acme\Shipping\Http\Controllers;

use Acme\Shipping\Plugin;
use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use CraftCms\Cms\Cp\Components\Field as CpField;
use CraftCms\Cms\Cp\Components\Input;
use CraftCms\Cms\Cp\Components\Lightswitch;
use Inertia\Response;
use InvalidArgumentException;

use function CraftCms\Cms\t;
use function CraftCms\Cms\ui;

class ShipmentsController
{
    use EnforcesPermissions;

    private const string PERMISSION = 'acme-shipping:manageShipments';

    /**
     * Door 6 — Vue, through `@craftcms/cp`.
     *
     * The page component is registered on `Cp.$inertia`; `vue` and
     * `@craftcms/cp` are externalized in vite.config.js so this runs on the
     * control panel's Vue instance and its Inertia router.
     */
    public function index(Request $request): Response
    {
        $this->requirePermission(self::PERMISSION);

        $search = trim((string) $request->query('search', ''));

        return Inertia::render('acme-shipping/Index', [
            'shipments' => $this->shipments($search),
            'carriers' => $this->carrierRates(),
            'searchTerm' => $search,
        ]);
    }

    /** Door 2 — a Twig screen built with `ui()`. */
    public function edit(string $id): CpScreenResponse
    {
        $entry = $this->entry($id);
        $shipment = $this->shipment($entry);

        return $this->screen($shipment)
            ->contentTemplate('acme-shipping/_shipment', [
                'shipment' => $shipment,
                'carriers' => $this->carriers(),
                'rate' => $this->quotedRate($entry),
                'testMode' => Plugin::getInstance()->getSettings()->testMode,
                'errors' => $this->errors(),
            ]);
    }

    /** Door 3 — the same components, from a Blade view. */
    public function rate(string $id): CpScreenResponse
    {
        $entry = $this->entry($id);
        $shipment = $this->shipment($entry);

        // Resolves to resources/views/_rate.blade.php — same namespace as the
        // Twig templates, different engine.
        return $this->screen($shipment)
            ->contentTemplate('acme-shipping/_rate', [
                'weight' => 1 + ((int) $entry->id % 9),
                'rate' => $this->quotedRate($entry),
                'testMode' => Plugin::getInstance()->getSettings()->testMode,
            ]);
    }

    /** The Craft 5 template, unchanged, for the compatibility section. */
    public function legacy(string $id): CpScreenResponse
    {
        $entry = $this->entry($id);
        $shipment = $this->shipment($entry);

        return $this->screen($shipment)
            ->contentTemplate('acme-shipping/_shipment-legacy', [
                'shipment' => $shipment,
                'carriers' => $this->carriers(),
                'errors' => $this->errors(),
            ]);
    }

    /**
     * Deliberate failures. The registry's exception lists every registered
     * name, so a typo answers "what can I use?" on its own.
     *
     * `?raw=1` rethrows instead of catching, for showing the real error page.
     */
    public function catalog(Request $request): CpScreenResponse
    {
        $this->requirePermission(self::PERMISSION);

        $cases = [
            [
                'label' => t('A name that is not registered'),
                'code' => "{{ ui('status', {status: 'live'}) }}",
                'note' => t('The message is the catalog. Note the last entry: this plugin registers acme-rate-badge in its boot(), so it is listed alongside core\'s.'),
                'run' => fn () => ui('status', ['status' => 'live']),
            ],
            [
                'label' => t('A config key that is not a setter'),
                'code' => "{{ ui('field', {labl: 'Tracking number'}) }}",
                'note' => t('Names the class and the key, but not the available setters. A macro would have ignored this silently.'),
                'run' => fn () => ui('field', ['labl' => 'Tracking number']),
            ],
            [
                'label' => t('A getter, passed as if it were a setter'),
                'code' => "{{ ui('field', {getId: 'tracking'}) }}",
                'note' => t('Rejected on purpose. PHP ignores extra arguments, so a getter would have swallowed the value and rendered nothing.'),
                'run' => fn () => ui('field', ['getId' => 'tracking']),
            ],
        ];

        $results = [];

        foreach ($cases as $case) {
            try {
                ($case['run'])();
                $message = null;
            } catch (InvalidArgumentException $e) {
                if ($request->boolean('raw')) {
                    throw $e;
                }

                $message = $e->getMessage();
            }

            $results[] = [
                'label' => $case['label'],
                'code' => $case['code'],
                'note' => $case['note'],
                'message' => $message,
            ];
        }

        return new CpScreenResponse()
            ->title(t('What happens when you get it wrong'))
            ->addCrumb(t('Shipments'), 'acme-shipping')
            ->contentTemplate('acme-shipping/_catalog', ['results' => $results]);
    }

    /**
     * Each example is built once and rendered twice — live, and as escaped
     * markup — so the two can never drift apart.
     */
    public function decisions(): CpScreenResponse
    {
        $this->requirePermission(self::PERMISSION);

        $examples = [
            [
                'title' => t('You pass errors'),
                'code' => <<<'PHP'
                    CpField::make()
                        ->label(t('Recipient'))
                        ->required()
                        ->errors([t('Recipient cannot be blank.')])
                        ->input(Input::make()->name('recipient'));
                    PHP,
                'component' => CpField::make()
                    ->label(t('Recipient'))
                    ->required()
                    ->errors([t('Recipient cannot be blank.')])
                    ->input(Input::make()->name('recipient')),
                'gave' => [
                    t('`has-errors` on the host, so the field can style itself'),
                    t('An error list in the `feedback` slot, labelled for screen readers'),
                    t('`required` reflected, so the asterisk and the semantics stay together'),
                ],
            ],
            [
                'title' => t('You pass a switch state'),
                'code' => <<<'PHP'
                    CpField::make()
                        ->label(t('Insured'))
                        ->input(Lightswitch::make()->name('insured')->on());
                    PHP,
                'component' => CpField::make()
                    ->label(t('Insured'))
                    ->input(Lightswitch::make()->name('insured')->on()),
                'gave' => [
                    t('`role="switch"` and `aria-checked`, not a styled checkbox'),
                    t('An always-post hidden input, so unchecking actually submits'),
                ],
            ],
            [
                'title' => t('You pass a maxlength'),
                'code' => <<<'PHP'
                    // Shrinks to the expected character width.
                    CpField::make()
                        ->label(t('Postcode'))
                        ->input(Input::make()->name('postcode')->maxlength(8));

                    // Same control, told to span the column anyway.
                    CpField::make()
                        ->label(t('Postcode'))
                        ->width('full')
                        ->input(Input::make()->name('postcode')->maxlength(8));
                    PHP,
                'component' => CpField::make()
                    ->label(t('Postcode'))
                    ->input(Input::make()->name('postcode')->maxlength(8)),
                'extra' => CpField::make()
                    ->label(t('Postcode'))
                    ->width('full')
                    ->input(Input::make()->name('postcode')->maxlength(8)),
                'gave' => [
                    t('A field spans its column until its control declares a `maxlength`'),
                    t('Then it shrinks, because a postcode box the width of the page reads as a bug'),
                    t('`width` overrides the inference in either direction: `full` or `auto`'),
                    t('The two markup panes differ by one attribute — the sizing itself happens in the browser, so screenshot the render, not the HTML'),
                ],
            ],
        ];

        return new CpScreenResponse()
            ->title(t('Decisions you did not make'))
            ->addCrumb(t('Shipments'), 'acme-shipping')
            ->contentTemplate('acme-shipping/_decisions', [
                'examples' => array_map(fn (array $example) => [
                    ...$example,
                    'html' => $this->indentHtml((string) $example['component']),
                    'extraHtml' => isset($example['extra'])
                        ? $this->indentHtml((string) $example['extra'])
                        : null,
                ], $examples),
            ]);
    }

    /** Presentation only: makes one-line component markup readable on a slide. */
    private function indentHtml(string $html): string
    {
        $void = ['input', 'br', 'hr', 'img'];
        $tokens = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        $out = [];
        $depth = 0;

        foreach ($tokens as $token) {
            if (! str_starts_with($token, '<')) {
                if (trim($token) !== '') {
                    $out[] = str_repeat('  ', $depth).trim($token);
                }

                continue;
            }

            $name = strtolower((string) preg_replace('/^<\/?([a-z0-9-]+).*$/is', '$1', $token));
            $closing = str_starts_with($token, '</');

            if ($closing) {
                $depth = max(0, $depth - 1);
            }

            $out[] = str_repeat('  ', $depth).$token;

            if (! $closing && ! in_array($name, $void, true) && ! str_ends_with($token, '/>')) {
                $depth++;
            }
        }

        return implode("\n", $out);
    }

    public function save(Request $request): RedirectResponse
    {
        $this->requirePermission(self::PERMISSION);

        $request->validate([
            'recipient' => ['required', 'string', 'max:255'],
            'carrier' => ['required', 'string'],
        ]);

        // Nothing is persisted: the example has no table of its own.
        Flash::success(t('Shipment saved.'));

        return back();
    }

    /** @param array<string, mixed> $shipment */
    private function screen(array $shipment): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title($shipment['tracking'])
            ->addCrumb(t('Shipments'), 'acme-shipping')
            ->action('acme-shipping/shipments/save')
            ->redirectUrl('acme-shipping')
            ->submitButtonLabel(t('Save shipment'))
            ->tabs([
                ['label' => t('Shipment'), 'url' => Url::cpUrl("acme-shipping/{$shipment['id']}")],
                ['label' => t('Rate'), 'url' => Url::cpUrl("acme-shipping/{$shipment['id']}/rate")],
                ['label' => t('Craft 5 template'), 'url' => Url::cpUrl("acme-shipping/{$shipment['id']}/legacy")],
            ]);
    }

    private function entry(string $id): Entry
    {
        $this->requirePermission(self::PERMISSION);

        $entry = Entry::find()->id((int) $id)->one();

        abort_if($entry === null, 404);

        return $entry;
    }

    /** @return array<string, list<string>> */
    private function errors(): array
    {
        return session('errors')?->getBag('default')->getMessages() ?? [];
    }

    private function quotedRate(Entry $entry): float
    {
        return 8.5 + (($entry->id ?? 0) % 40) * 1.75;
    }

    /**
     * Rate inputs for the Vue page, which does the arithmetic itself.
     *
     * @return list<array{handle: string, name: string, service: string, base: float, perKg: float, etaDays: int}>
     */
    private function carrierRates(): array
    {
        return [
            ['handle' => 'acme-ground', 'name' => t('Acme Ground'), 'service' => t('Standard'), 'base' => 8.50, 'perKg' => 1.75, 'etaDays' => 5],
            ['handle' => 'acme-air', 'name' => t('Acme Air'), 'service' => t('Next day'), 'base' => 24.00, 'perKg' => 3.40, 'etaDays' => 1],
            ['handle' => 'acme-freight', 'name' => t('Acme Freight'), 'service' => t('Palletised'), 'base' => 42.00, 'perKg' => 0.60, 'etaDays' => 7],
            ['handle' => 'acme-express', 'name' => t('Acme Express'), 'service' => t('Two day'), 'base' => 15.25, 'perKg' => 2.20, 'etaDays' => 2],
        ];
    }

    /**
     * `Select` takes a list of `{label, value}`, which is also what the legacy
     * `forms.selectField` macro accepts — so one shape serves both screens.
     *
     * @return list<array{label: string, value: string}>
     */
    private function carriers(): array
    {
        return [
            ['label' => t('Acme Ground'), 'value' => 'acme-ground'],
            ['label' => t('Acme Air'), 'value' => 'acme-air'],
            ['label' => t('Acme Freight'), 'value' => 'acme-freight'],
        ];
    }

    /** @return array<string, mixed> */
    private function shipment(Entry $entry): array
    {
        $live = $entry->getStatus() === 'live';

        return [
            'id' => $entry->id,
            'tracking' => sprintf('ACME-%08d', (int) $entry->id),
            'recipient' => (string) $entry->title,
            'carrier' => 'acme-ground',
            'carrierLabel' => t('Acme Ground'),
            'status' => $live ? 'live' : 'pending',
            'statusLabel' => $live ? t('In transit') : t('Held'),
            'signature' => $live,
            'insured' => true,
            'webhookUrl' => '',
            'entryUrl' => $entry->getCpEditUrl(),
        ];
    }

    /**
     * Entries standing in for shipment records, so the example has real data
     * without a table of its own.
     *
     * @return list<array{id: int|null, tracking: string, label: string, url: string|null, shipmentUrl: string, status: string, createdAt: string|null}>
     */
    private function shipments(string $search = ''): array
    {
        $entries = Entry::find()
            ->orderBy('dateCreated', 'desc')
            ->limit(25)
            ->all();

        if ($search !== '') {
            $entries = array_filter(
                $entries,
                fn (Entry $entry) => stripos((string) $entry->title, $search) !== false
                    || stripos(sprintf('ACME-%08d', (int) $entry->id), $search) !== false,
            );
        }

        return array_values(array_map(fn (Entry $entry) => [
            'id' => $entry->id,
            'tracking' => sprintf('ACME-%08d', (int) $entry->id),
            'label' => (string) $entry->title,
            'url' => $entry->getCpEditUrl(),
            'shipmentUrl' => Url::cpUrl("acme-shipping/{$entry->id}"),
            'status' => $entry->getStatus() === 'live' ? 'In transit' : 'Held',
            'createdAt' => $entry->dateCreated?->format(DATE_ATOM),
        ], $entries));
    }
}
