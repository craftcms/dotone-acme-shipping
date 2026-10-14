/**
 * `@craftcms/cp` is published by the control panel's import map at runtime, not
 * installed from npm, so the specifier needs declaring locally.
 */
declare module '@craftcms/cp' {
  import type {DefineComponent} from 'vue';

  export const AdminTable: DefineComponent<any, any, any>;
  export const SearchForm: DefineComponent<any, any, any>;
  export const ActionMenu: DefineComponent<any, any, any>;

  export function useAppLayout(
    options: Record<string, unknown> | (() => Record<string, unknown>)
  ): void;

  export function useCraftTable(options: Record<string, unknown>): any;

  export function createCraftColumnHelper<T>(): any;

  export type InertiaPageComponent = DefineComponent<any, any, any>;
}

interface Window {
  Cp: CpInstance;
}

interface CpInstance {
  /** Whatever the control panel was configured with, translations included. */
  initialConfig: Record<string, unknown>;
  booting(callback: (cp: CpInstance) => void): void;
  $inertia: {register(name: string, page: unknown): void};
  $components: {register(name: string, component: unknown): void};
  $elementDetailsPanels: {register(descriptor: Record<string, unknown>): void};
}
