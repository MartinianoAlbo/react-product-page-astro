import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwind from '@astrojs/tailwind';

// Cargar variables de entorno
import dotenv from 'dotenv';
dotenv.config();

export default defineConfig({
  integrations: [
    react(),
    tailwind(),
  ],

  // Base dinámica: raíz en dev, ruta de plugin en prod
  base: process.env.NODE_ENV === 'development'
    ? '/'
    : '/wp-content/plugins/react-product-page/astro-product-page-app/dist',

  // Generar archivos estáticos para que WP los lea con file_exists
  output: 'static',

  // Configuración de build
  build: {
    // Directorio de salida relativo a este config
    outDir: 'dist',
    // Cada página en su propio directorio (e.g. dist/product/slug/index.html)
    format: 'directory',
    // Carpeta de assets con hash para cache busting
    assets: '_astro',
    // En desarrollo, no inlinear CSS para debugging
    inlineStylesheets: 'never',
  },

  // Configuración del servidor de desarrollo de Astro
  server: {
    port: 3000,
    host: true, // Habilita acceso desde otras IPs (e.g. tu VPS o red local)
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-WP-Nonce',
    },
  },

  // Configuración de Vite
  vite: {
    build: {
      // Genera un manifest.json con mapping de assets
      manifest: true,
      rollupOptions: {
        output: {
          // Rutas y nombres de archivos
          entryFileNames: 'js/[name].[hash].js',
          chunkFileNames: 'js/[name].[hash].js',
          assetFileNames: 'assets/[name].[hash][extname]',
        },
      },
    },
    server: {
      cors: true,
      proxy: {
        // Proxy para llamadas a la REST API de WP durante el dev
        '/wp-json': {
          target: process.env.WP_API_URL || 'http://saphirus.local/wp-json',
          changeOrigin: true,
          secure: false,
        },
      },
    },
  },
  env: {
    schema: {
      WP_API_URL: { context: 'client',  access: 'public', type: 'string' },
      WC_CONSUMER_KEY: { context: 'server', access: 'secret', type: 'string' },
      WC_CONSUMER_SECRET: { context: 'server', access: 'secret', type: 'string' },
    }
  },
});
