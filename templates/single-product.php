<?php
/**
 * Template para producto único - React Product Page
 * Este template reemplaza el single-product.php de WooCommerce
 */

// Obtener slug e ID del producto
global $product;
if ( is_string( $product ) && '' !== $product ) {
    $product_slug = $product;
    $product_id   = (int) ( get_page_by_path( $product_slug, OBJECT, 'product' )->ID ?? get_the_ID() );
} elseif ( $product instanceof \WC_Product ) {
    $product_slug = $product->get_slug();
    $product_id   = $product->get_id();
} else {
    $product_obj  = wc_get_product( get_the_ID() );
    $product_slug = $product_obj->get_slug();
    $product_id   = $product_obj->get_id();
}

// Obtener header de WordPress
get_header('shop');

// Preparar datos para Astro
$product_data = [
    'id'     => $product_id,
    'slug'   => $product_slug,
    'apiUrl' => rest_url('rpp/v1/product/slug/' . $product_slug),
    'nonce'  => wp_create_nonce('wp_rest')
];


$is_development = defined('WP_DEBUG') && WP_DEBUG;
$astro_url = $is_development ? RPP_ASTRO_DEV_URL : RPP_ASTRO_URL;
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        
        <?php
        /**
         * Hook: woocommerce_before_main_content
         * Mantener compatibilidad con temas
         */
        do_action('woocommerce_before_main_content');
        ?>
        
        <div id="react-product-root" 
             data-product-id="<?php echo esc_attr( $product_id ); ?>"
             data-product-slug="<?php echo esc_attr( $product_slug ); ?>"
             data-api-url="<?php echo esc_url( $product_data['apiUrl'] ); ?>"
             data-nonce="<?php echo esc_attr( $product_data['nonce'] ); ?>">
            
                <!-- En producción: cargar el HTML generado por Astro -->
                <?php
                // construyo la URL de Astro y traigo el HTML
                $astro_page_url = trailingslashit( $astro_url ) . 'product/' . $product_slug;
                error_log(print_r($astro_page_url, true));
                $resp = wp_remote_get( $astro_page_url, [
                    'timeout'   => 5,
                    'sslverify' => false,
                    'headers'   => [
                        'Accept' => 'text/html',
                    ],
                ] );
                error_log(print_r($resp, true));
                if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) {
                    $astro_html = false;
                } else {
                    $astro_html = wp_remote_retrieve_body( $resp );
                }

                if ( $astro_html ) {
                    echo $astro_html;
                } else {
                    ?>
                    <div class="woocommerce-error">
                        <p>La página del producto no está disponible en este momento.</p>
                    </div>
                    <?php
                }
                ?>
            
        </div>
        
        <?php
        /**
         * Hook: woocommerce_after_main_content
         */
        do_action('woocommerce_after_main_content');
        ?>
        
    </main>
</div>

<?php
// Obtener sidebar si el tema lo usa
do_action('woocommerce_sidebar');

// Obtener footer de WordPress
get_footer('shop');
?>