{{--
    Door 3 — Blade.

    `HasViews` registers `resources/views` and `resources/templates` under the
    same `acme-shipping` namespace, so this view and `_shipment.twig` are
    siblings as far as the CP is concerned. The components are identical; only
    the engine differs.

    `@use` is needed because Blade compiles into the global namespace, where the
    namespaced `ui()` helper isn't visible. And plain `{{ }}` is correct — every
    component implements `Htmlable`, so Laravel renders it instead of escaping
    it. No `{!! !!}`.
--}}
@use('function CraftCms\Cms\t')
@use('function CraftCms\Cms\ui')

@if ($testMode)
    {{ ui('callout', [
        'variant' => 'warning',
        'content' => t('Sandbox rates. Do not quote these to a customer.'),
    ]) }}
@endif

{{ ui('pane', [
    'appearance' => 'raised',
    'label' => t('Rate quote'),
    'content' => ui('field', [
        'label' => t('Billable weight'),
        'instructions' => t('Rounded up to the next whole kilogram.'),
        'input' => ui('input', [
            'id' => 'weight',
            'name' => 'weight',
            'value' => $weight,
            'type' => 'number',
            'min' => 1,
            'suffix' => 'kg',
        ]),
    ]),
]) }}

<div class="flex items-center gap-2 mt-4">
    <span class="text-sm">{{ t('Quoted') }}</span>
    {{ ui('acme-rate-badge', ['amount' => $rate]) }}
</div>
