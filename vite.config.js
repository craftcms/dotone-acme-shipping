import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  build: {
    emptyOutDir: true,
    rollupOptions: {
      // Supplied by the control panel's import map, so we run on its instances
      // rather than bundling a second Vue and a second Inertia router.
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
