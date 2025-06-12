import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwind from '@astrojs/tailwind';
import node from '@astrojs/node';

// Cargar variables de entorno
import dotenv from 'dotenv';
dotenv.config();

// https://astro.build/config
export default defineConfig({
  integrations: [
    react(),
    tailwind()
  ],
  
  // Variables de entorno disponibles en el cliente
  env: {
    schema: {
      WP_API_URL: {
        context: 'client',
        access: 'public',
        type: 'string'
      },
      WC_CONSUMER_KEY: {
        context: 'server',
        access: 'secret',
        type: 'string'
      },
      WC_CONSUMER_SECRET: {
        context: 'server', 
        access: 'secret',
        type: 'string'
      }
    }
  },
  
  // Base URL para producción (ajustar según tu setup)
  base: '/wp-content/plugins/react-product-page/astro-app/dist',
  
  // Output server: todas las páginas se renderizan en el servidor
  output: 'server',
  
  // Adaptador para Node.js (necesario para hybrid/server)
  adapter: node({
    mode: 'standalone'
  }),
  
  // Build configuration
  build: {
    // Formato de archivos para mejor caché
    format: 'directory',
    
    // Assets con hash para cache busting
    assets: '_astro',
    
    // En desarrollo, no inlinear CSS para facilitar debugging
    inlineStylesheets: 'never'
  },
  
  // Server configuration para desarrollo
  server: {
    port: 3000,
    host: true, // Permite acceso desde WordPress
    
    // Headers CORS para desarrollo
    headers: {
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
      "Access-Control-Allow-Headers": "Content-Type, Authorization, X-WP-Nonce"
    }
  },
  
  // Vite configuration
  vite: {
    build: {
      rollupOptions: {
        output: {
          // Nombres de archivos más limpios
          entryFileNames: 'js/[name].[hash].js',
          chunkFileNames: 'js/[name].[hash].js',
          assetFileNames: 'assets/[name].[hash][extname]'
        }
      }
    },
    
    // Para desarrollo con WordPress
    server: {
      cors: true,
      proxy: {
        // Proxy para la API de WordPress
        '/wp-json': {
          target: process.env.WP_API_URL || 'http://saphirus.local/wp-json', // Usar .env
          changeOrigin: true,
          secure: false
        }
      }
    }
  }
});