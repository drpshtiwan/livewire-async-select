import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        'async-select': 'resources/css/async-select.css',
        'async-select-bootstrap-v4': 'resources/css/async-select-bootstrap-v4.css',
      },
      output: {
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? '';

          if (name.includes('async-select-bootstrap-v4')) {
            return 'async-select-bootstrap-v4.css';
          }

          if (name.includes('async-select')) {
            return 'async-select.css';
          }

          return 'assets/[name]-[hash][extname]';
        },
      },
    },
    minify: true,
  },
  css: {
    postcss: './postcss.config.js',
  },
});
