# Acme Shipping

An example Craft 6 plugin. Nothing here ships a real shipment — the point is
that **each surface deliberately uses a different way of reaching the control
panel's component library**, so you can read one file per approach and compare.

Entries stand in for shipment records so the plugin works on any install.

## The five doors

There is one component library — 69 `craft-*` web components in `@craftcms/ui`.
These are the ways into it.

| Door | Surface | File |
|---|---|---|
| **1. Raw web components** | Status chips on the shipment screen | `resources/templates/_shipment.twig` |
| **2. Twig + `ui()`** | The shipment screen | `resources/templates/_shipment.twig` |
| **3. Blade + `ui()`** | The rate screen | `resources/views/_rate.blade.php` |
| **4. PHP components** | The entry metadata rows | `src/Listeners/AddTrackingMetadata.php` |
| **5. Ui/Node API** | The settings screen | `src/Plugin.php` → `settingsUi()` |
| **6. Vue + `@craftcms/cp`** | Index page and details panel | `resources/js/` |

Doors 1–4 all end in the same place: server-rendered HTML containing a Lit
custom element. Door 5 is different in kind — it emits a JSON payload that Vue
mounts, so values, errors and delta tracking belong to Craft rather than to you.

## Where to look

**`src/Plugin.php`** — `settingsUi()` builds the settings screen out of
`Ui\Nodes` and `Ui\Controls` with no markup anywhere. The `BooleanMenu` field is
the clearest argument for this door: `includeEnvVars()` lists the boolean
environment variables and `optionValue()` normalizes whatever the setting
holds.

`boot()` registers this plugin's own component into the shared registry, so
`ui('acme-rate-badge', {...})` works in any template, including other people's.

**`src/Cp/Components/RateBadge.php`** — that component. Extending
`ViewComponent` and implementing `tagName()` plus `hostAttributes()` is the
whole contract; the base class handles attribute merging, slots and escaping.

**`resources/templates/_shipment.twig`** — door 2, with a labelled door 1 block
inside it. `craft-status` and `craft-badge` have no PHP wrappers (35 of the 69
components don't), so there the tag is the API.

**`resources/views/_rate.blade.php`** — door 3. `HasViews` registers
`resources/views` and `resources/templates` under the same `acme-shipping`
namespace, so the Blade and Twig screens are siblings to the CP. Note `@use`,
and note that plain `{{ }}` is correct: components implement `Htmlable`, so
Laravel renders rather than escapes them.

**`src/Listeners/AddTrackingMetadata.php`** — door 4. A listener has nowhere to
put a template, so components are built and `->toHtml()`'d directly.

## When you get it wrong

`acme-shipping/catalog` renders three deliberate `ui()` mistakes and what each
one throws. The first is the useful one: an unregistered name produces a message
listing every registered component, including this plugin's own
`acme-rate-badge`, so a typo answers "what can I use?" by itself.

The other two show the limits of that. An unknown config key names the class and
the key but not the available setters, and a getter passed as a setter is
rejected rather than silently swallowing the value.

Add `?raw=1` to let the first one throw for real instead of being caught.

## Backwards compatibility

`resources/templates/_shipment-legacy.twig` is the Craft 5 version of the
shipment screen, unchanged, reachable at `acme-shipping/{id}/legacy`. Open it
next to `acme-shipping/{id}` to see the same `forms.*` macros now emitting
`craft-*` components.

The macro surface didn't move: 77 macros in Craft 5, 77 in Craft 6, with
`forms.copytextField` → `forms.copyTextField` the only rename.

## Two things that will bite you

**Twig escapes chained setters.** `ui()` is marked `is_safe: html`, but that
covers the bare call. `{{ ui('button', {...}).href(url) }}` comes out escaped —
put every setter in the config hash, or add `|raw`. Blade doesn't have this
problem, because `e()` honours `Htmlable`.

**Blade needs the namespace.** Blade compiles into the global namespace, so
`ui()` isn't visible. `@use('function CraftCms\Cms\ui')` or fully qualify it.

## Screens

| URL | Door |
|---|---|
| `acme-shipping` | Vue / Inertia index |
| `acme-shipping/{id}` | Twig + `ui()` |
| `acme-shipping/{id}/rate` | Blade + `ui()` |
| `acme-shipping/{id}/legacy` | Craft 5 macros, via the shim |
| `acme-shipping/catalog` | Deliberate `ui()` failures |
| Settings → Acme Shipping | Ui/Node API |

Plus the "Tracking number" and "Quoted rate" rows on any entry's metadata list,
and the Shipments panel in the element editor.

## Install

```sh
composer require acme/shipping
php craft plugin/install acme-shipping
```

Front-end assets build with `npm install && npm run build`. `vue` and
`@craftcms/cp` are externalized in `vite.config.js` — they come from the control
panel's import map at runtime, so bundling them would ship a second Vue and a
second Inertia router.
