<?php

declare(strict_types=1);

namespace Acme\Shipping\Listeners;

use Acme\Shipping\Cp\Components\RateBadge;
use CraftCms\Cms\Cp\Components\CopyAttribute;
use CraftCms\Cms\Element\Events\ElementMetadataResolving;
use CraftCms\Cms\Entry\Elements\Entry;

use function CraftCms\Cms\t;

/**
 * Door 4 — PHP components, no template.
 *
 * A listener has nowhere to put a template, so the components are built and
 * rendered to strings directly. Every component is `Htmlable` and
 * `Stringable`, which is all the metadata list wants.
 */
class AddTrackingMetadata
{
    public function handle(ElementMetadataResolving $event): void
    {
        if (! $event->element instanceof Entry) {
            return;
        }

        // Resolved lazily: a closure returning `false` drops the row, so the
        // components are only built for elements that have been saved.
        $event->metadata[t('Tracking number')] = function () use ($event) {
            $number = $this->trackingNumber($event->element);

            return $number !== null
                ? CopyAttribute::make()->value($number)->toHtml()
                : false;
        };

        $event->metadata[t('Quoted rate')] = fn () => $event->element->id !== null
            ? RateBadge::make()->amount($this->rate($event->element))->toHtml()
            : false;
    }

    /** Stands in for a real lookup, so the row shows up on any entry. */
    private function trackingNumber(Entry $entry): ?string
    {
        return $entry->id === null ? null : sprintf('ACME-%08d', $entry->id);
    }

    private function rate(Entry $entry): float
    {
        return 8.5 + (($entry->id ?? 0) % 40) * 1.75;
    }
}
