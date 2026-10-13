/**
 * `@craftcms/cp` is published by the control panel's import map at runtime, not
 * installed from npm, so the specifier needs declaring locally.
 */
declare module '@craftcms/cp' {
  import type {Component, DefineComponent} from 'vue';

  export const AdminTable: DefineComponent<any, any, any>;

  export function useAppLayout(options: Record<string, unknown> | (() => Record<string, unknown>)): void;

  export function useCraftTable(options: Record<string, unknown>): any;

  export function createCraftColumnHelper<T>(): any;

  export type InertiaPageComponent = DefineComponent<any, any, any>;
}

interface Window {
  Cp: {
    booting(callback: (cp: CpInstance) => void): void;
  };
}

interface CpInstance {
  $inertia: {register(name: string, page: unknown): void};
  $components: {register(name: string, component: unknown): void};
  $elementDetailsPanels: {register(descriptor: Record<string, unknown>): void};
}
