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
    public function index(): Response
    {
        $this->requirePermission(self::PERMISSION);

        return Inertia::render('acme-shipping/Index', [
            'shipments' => $this->shipments(),
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
    private function shipments(): array
    {
        $entries = Entry::find()
            ->orderBy('dateCreated', 'desc')
            ->limit(25)
            ->all();

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
