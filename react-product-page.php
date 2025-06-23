<?php
/**
 * Plugin Name: React Product Page
 * Description: Reemplaza la página de producto de WooCommerce con una app Astro + React
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: react-product-page
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('RPP_VERSION', '1.0.0');
define('RPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RPP_ASTRO_URL', 'https://astro-wc-product-page.vercel.app/dist');
define('RPP_ASTRO_DEV_URL', 'https://192.168.0.248:4321/');


/**
 * Clase principal del plugin
 */
class React_Product_Page {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Verificar requisitos
        add_action('plugins_loaded', [$this, 'check_requirements']);
        
        // Override del template
        add_filter('template_include', [$this, 'override_product_template'], 99);
        
        // Añadir configuración CORS para desarrollo
        add_action('init', [$this, 'handle_cors']);

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        require_once RPP_PLUGIN_DIR . 'includes/class-rpp-api.php';
        $api = new RPP_API();
        $api->register_routes();
    }
    
    public function check_requirements() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('React Product Page requiere WooCommerce.', 'react-product-page'); ?></p>
                </div>
                <?php
            });
            return false;
        }
    }
    
    /**
     * Override del template de producto único
     */
    public function override_product_template($template) {
        if (is_product()) {
            $custom_template = RPP_PLUGIN_DIR . 'templates/single-product.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    
    /**
     * Manejar CORS para desarrollo
     */
    public function handle_cors() {
        // Solo en desarrollo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            header("Access-Control-Allow-Origin: " . RPP_ASTRO_DEV_URL);
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce");
            
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                exit(0);
            }
        }
    }
}

// Inicializar plugin
add_action('plugins_loaded', function() {
    React_Product_Page::get_instance();
});