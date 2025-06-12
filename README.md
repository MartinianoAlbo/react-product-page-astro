# React Product Page

React Product Page is a WordPress plugin that overrides the default WooCommerce single product page with an [Astro](https://astro.build/) + React application. Product data is exposed through custom REST API endpoints and rendered by the Astro frontend located in `astro-product-page-app`.

## Development prerequisites

- Node.js 18 or higher
- NPM (or PNPM)
- A WordPress installation with WooCommerce enabled

## Building the Astro app

1. Copy `astro-product-page-app/env-example` to `astro-product-page-app/.env` and update the values so the Astro app can access your WordPress site.
2. Install dependencies:
   ```bash
   cd astro-product-page-app
   npm install
   ```
3. Start the development server (optional):
   ```bash
   npm run dev
   ```
   The dev server runs at `http://localhost:3000`, which matches the `RPP_ASTRO_URL` constant defined in `react-product-page.php`.
4. Build the production files:
   ```bash
   npm run build
   ```
   The compiled site is generated inside `astro-product-page-app/dist`. These files are served by the plugin when not running the dev server.

## Plugin installation

1. Copy this plugin folder into `wp-content/plugins` of your WordPress installation.
2. Activate **React Product Page** from the Plugins screen.
3. Ensure WooCommerce is active; otherwise the plugin will display an admin notice.
4. During development keep the Astro dev server running. For production deploy the plugin with the contents of `astro-product-page-app/dist` built.

