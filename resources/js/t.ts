/**
 * The control panel keeps its translations in its own copy of
 * `@craftcms/ui/utilities/translate`, and that module's store is module-scoped.
 * A plugin that bundles the same module gets a separate, empty store, so `t()`
 * imported from there returns the untranslated key. `@craftcms/ui` is also not
 * published through the control panel's import map, so there is no shared
 * instance to borrow.
 *
 * Until one of those changes, read the store the control panel was handed.
 */
const store = (): Record<string, Record<string, string>> =>
  (window.Cp?.initialConfig?.translations as
    | Record<string, Record<string, string>>
    | undefined) ?? {};

export function t(
  message: string,
  params: Record<string, string | number> = {},
  category = 'acme-shipping'
): string {
  const translated = store()[category]?.[message] ?? message;

  return Object.entries(params).reduce(
    (out, [key, value]) => out.replaceAll(`{${key}}`, String(value)),
    translated
  );
}
