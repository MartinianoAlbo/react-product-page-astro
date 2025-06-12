/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
  theme: {
    extend: {
      colors: {
        // Colores personalizados para coincidir con tu tema WooCommerce
        primary: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
        woo: {
          purple: '#7F54B3',
          pink: '#C7649B',
          green: '#0F8A65',
        }
      },
      fontFamily: {
        // Usar las fuentes del tema WordPress si es necesario
        sans: ['system-ui', '-apple-system', 'sans-serif'],
      }
    },
  },
  plugins: [],
  // Prefix para evitar conflictos con clases del tema
  prefix: 'rpp-',
  // Configuración para WordPress
  corePlugins: {
    preflight: true, // Activar estilos base pero con prefix
    container: false, // Desactivar container por conflictos con temas
  },
}