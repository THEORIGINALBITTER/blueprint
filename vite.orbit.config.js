import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
  build: {
    lib: {
      entry: resolve(process.cwd(), 'src/orbit-menu.jsx'),
      name: 'BlueprintZenOrbit',
      formats: ['iife'],
      fileName: () => 'orbit-menu.js',
    },
    outDir: 'orbit-dist',
    emptyOutDir: true,
  },
});
