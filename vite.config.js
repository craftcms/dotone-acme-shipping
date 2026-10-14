import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  build: {
    emptyOutDir: true,
    rollupOptions: {
      // Supplied by the control panel's import map, so we run on its instances
      // rather than bundling a second Vue and a second Inertia router.
      //
      // `@craftcms/ui` is deliberately not in this list, because it is not in
      // the import map either — there is nothing to be external to. Bundling
      // its barrel would ship a second Lit and lose the race to define the
      // custom elements, so this plugin imports nothing from it; see
      // resources/js/t.ts.
      external: ['vue', '@craftcms/cp'],
    },
  },
  plugins: [
    laravel({
      input: ['resources/js/cp.ts'],
      hotFile: 'public/hot',
      buildDirectory: 'build',
      publicDirectory: 'public',
      refresh: false,
    }),
    vue(),
  ],
});
