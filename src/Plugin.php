<?php

declare(strict_types=1);

namespace Acme\Shipping;

use Acme\Shipping\Cp\Components\RateBadge;
use Acme\Shipping\Listeners\AddTrackingMetadata;
use Acme\Shipping\Models\Settings;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Element\Events\ElementMetadataResolving;
use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Ui\Controls\BooleanMenu;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Data\Permission;
use Override;

use function CraftCms\Cms\t;

class Plugin extends BasePlugin
{
    public bool $hasCpSection = true;

    public bool $hasCpSettings = true;

    public bool $hasReadOnlyCpSettings = true;

    /** @var array<class-string, class-string> */
    protected array $events = [
        ElementMetadataResolving::class => AddTrackingMetadata::class,
    ];

    /** @var array{input: list<string>} */
    protected array $vite = [
        'input' => ['resources/js/cp.ts'],
    ];

    public function boot(): void
    {
        // Registering under a prefixed name keeps it from colliding with core's
        // or another plugin's.
        $this->app->make(ComponentRegistry::class)->register('acme-rate-badge', RateBadge::class);
    }

    #[Override]
    protected static function createSettings(): ?PluginSettings
    {
        return new Settings();
    }

    /**
     * Door 5 — the Ui/Node API.
     *
     * No markup and no template: the nodes become a payload Vue mounts, and
     * values, errors and env-var references are Craft's problem from here.
     */
    #[Override]
    public function settingsUi(UiContext $context = new UiContext): ?Ui
    {
        $settings = $this->getSettings();

        return Ui::make([
            UiField::make(t('API key'))
                ->instructions(t('From your Acme dashboard.'))
                ->control(
                    Text::make('apiKey')
                        ->value($settings->apiKey)
                        ->placeholder('acme_live_…')
                ),

            UiField::make(t('Test mode'))
                ->instructions(t('Quote against the sandbox instead of live rates.'))
                ->control(
                    Lightswitch::make('testMode')->value($settings->testMode)
                ),

            // `includeEnvVars()` is the clearest argument for this door: the
            // control lists the boolean env vars and normalizes whatever the
            // setting holds, which a hand-built menu would have to redo.
            UiField::make(t('Require signature'))
                ->instructions(t('Can be set per environment.'))
                ->control(
                    BooleanMenu::make('requireSignature')
                        ->value(BooleanMenu::optionValue($settings->requireSignature))
                        ->yesLabel(t('Always'))
                        ->noLabel(t('Never'))
                        ->includeEnvVars()
                ),
        ]);
    }

    #[Override]
    protected function getPermissions(): array
    {
        return [
            new Permission(
                key: 'acme-shipping:manageShipments',
                label: t('Manage shipments'),
            ),
        ];
    }
}
