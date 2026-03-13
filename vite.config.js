import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
  logLevel: 'info',
  resolve: {
    alias: { '@': path.resolve(__dirname, 'resources/js') },
  },
  plugins: [
    tailwindcss(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/passkey-login.js'],
      refresh: true,
    }),
  ],
  css: {
    transformer: 'lightningcss',
    lightningcss: { errorRecovery: true, drafts: { customMedia: true } },
    postcss: { plugins: [] },
  },
  build: {
    sourcemap: false,
    minify: 'esbuild',
    cssMinify: true,
    cssCodeSplit: true,
    target: 'es2020',
    outDir: 'public/build',
    emptyOutDir: true,
    chunkSizeWarningLimit: 4000,
    rollupOptions: {
      output: {
        compact: true,
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) return 'assets/css/[name]-[hash].css'
          return 'assets/js/[name]-[hash].[ext]'
        },
      },
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    hmr: { overlay: false },
    watch: { usePolling: true },
  },
  optimizeDeps: {
    exclude: ['laravel-vite-plugin'],
    include: ['flowbite', 'alpinejs', 'axios', '@laragear/webpass'],
  },
  assetsInclude: ['**/*.svg', '**/*.png', '**/*.jpg', '**/*.woff', '**/*.woff2'],
})
