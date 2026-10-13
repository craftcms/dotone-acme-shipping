<?php

declare(strict_types=1);

namespace Acme\Shipping\Cp\Components;

use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Support\Html;
use Override;

/**
 * A plugin-supplied component, registered as `acme-rate-badge` so templates can
 * reach it through `ui()` the same way they reach core's.
 *
 * Subclassing {@see ViewComponent} is all it takes: the base handles attribute
 * merging, slot assignment and HTML escaping.
 */
class RateBadge extends ViewComponent
{
    protected ?float $amount = null;

    protected string $currency = '$';

    /** Rates above this read as expensive, so the badge warns instead of informs. */
    private const float HIGH_RATE = 50.0;

    #[Override]
    protected function tagName(): string
    {
        return 'craft-badge';
    }

    public function amount(float|int|string|null $amount): static
    {
        $this->amount = $amount === null ? null : (float) $amount;

        return $this;
    }

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            'fill' => $this->amount !== null && $this->amount >= self::HIGH_RATE ? 'red' : 'teal',
        ];
    }

    #[Override]
    protected function renderSlots(): string
    {
        $label = $this->amount === null
            ? '—'
            : $this->currency.number_format($this->amount, 2);

        return Html::encode($label).parent::renderSlots();
    }
}
