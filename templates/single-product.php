<?php
/**
 * Template para producto único - React Product Page
 * Este template reemplaza el single-product.php de WooCommerce
 */

// Obtener el producto actual
global $product;
if (!$product) {
    $product = wc_get_product(get_the_ID());
}

// Obtener header de WordPress
get_header('shop');

// Preparar datos para Astro
$product_data = [
    'id' => $product->get_id(),
    'slug' => $product->get_slug(),
    'apiUrl' => rest_url('rpp/v1/product/slug/' . $product->get_slug()),
    'nonce' => wp_create_nonce('wp_rest')
];

// En desarrollo, cargar desde Astro dev server
// En producción, cargar desde los archivos built
$is_development = defined('WP_DEBUG') && WP_DEBUG;
$astro_url = $is_development ? RPP_ASTRO_URL : RPP_PLUGIN_URL . 'astro-app/dist';
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
             data-product-id="<?php echo esc_attr($product->get_id()); ?>"
             data-product-slug="<?php echo esc_attr($product->get_slug()); ?>"
             data-api-url="<?php echo esc_url($product_data['apiUrl']); ?>"
             data-nonce="<?php echo esc_attr($product_data['nonce']); ?>">
            
            <?php if ($is_development): ?>
                <!-- En desarrollo: iframe a Astro dev server -->
                <div id="astro-dev-container" style="width: 100%; min-height: 600px;">
                    <script>
                        // Cargar Astro via fetch para evitar problemas de CORS
                        fetch('<?php echo RPP_ASTRO_URL; ?>/product/<?php echo $product->get_slug(); ?>')
                            .then(response => response.text())
                            .then(html => {
                                // Extraer solo el contenido del body
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                
                                // Cargar los estilos CSS de Astro
                                const stylesheets = doc.querySelectorAll('link[rel="stylesheet"]');
                                stylesheets.forEach(link => {
                                    if (!document.querySelector(`link[href="${link.href}"]`)) {
                                        const newLink = document.createElement('link');
                                        newLink.rel = 'stylesheet';
                                        newLink.href = link.href;
                                        document.head.appendChild(newLink);
                                    }
                                });
                                
                                // Cargar los estilos inline
                                const inlineStyles = doc.querySelectorAll('style');
                                inlineStyles.forEach(style => {
                                    const newStyle = document.createElement('style');
                                    newStyle.textContent = style.textContent;
                                    document.head.appendChild(newStyle);
                                });
                                
                                // Cargar el contenido
                                const content = doc.querySelector('#product-content');
                                if (content) {
                                    document.getElementById('astro-dev-container').innerHTML = content.innerHTML;
                                    
                                    // Ejecutar scripts de Astro
                                    const scripts = doc.querySelectorAll('script');
                                    scripts.forEach(script => {
                                        const newScript = document.createElement('script');
                                        if (script.src) {
                                            newScript.src = script.src;
                                        } else {
                                            newScript.textContent = script.textContent;
                                        }
                                        document.body.appendChild(newScript);
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error cargando Astro:', error);
                                document.getElementById('astro-dev-container').innerHTML = 
                                    '<p>Error cargando la página. Asegúrate de que Astro está corriendo en ' + 
                                    '<?php echo RPP_ASTRO_URL; ?></p>';
                            });
                        
                        // Cargar CSS de Tailwind directamente desde Astro
                        setTimeout(() => {
                            if (!document.querySelector('link[href*="_astro"]')) {
                                // Intentar cargar CSS de Astro si no se cargó automáticamente
                                fetch('<?php echo RPP_ASTRO_URL; ?>/_astro/')
                                    .then(response => response.text())
                                    .then(data => {
                                        // Buscar archivos CSS en el directorio _astro
                                        const cssMatches = data.match(/href="[^"]*\.css"/g);
                                        if (cssMatches) {
                                            cssMatches.forEach(match => {
                                                const href = match.replace('href="', '').replace('"', '');
                                                const link = document.createElement('link');
                                                link.rel = 'stylesheet';
                                                link.href = '<?php echo RPP_ASTRO_URL; ?>/' + href;
                                                document.head.appendChild(link);
                                            });
                                        }
                                    })
                                    .catch(() => {
                                        // Fallback: cargar Tailwind CDN para emergencia
                                        const fallbackLink = document.createElement('link');
                                        fallbackLink.rel = 'stylesheet';
                                        fallbackLink.href = 'https://cdn.tailwindcss.com';
                                        document.head.appendChild(fallbackLink);
                                        console.log('Usando Tailwind CDN como fallback');
                                    });
                            }
                        }, 1000);
                    </script>
                </div>
            <?php else: ?>
                <!-- En producción: cargar el HTML generado por Astro -->
                <?php
                $astro_html_file = RPP_PLUGIN_DIR . 'astro-app/dist/product/' . $product->get_slug() . '/index.html';
                if (file_exists($astro_html_file)) {
                    // Leer y mostrar el HTML de Astro
                    $html = file_get_contents($astro_html_file);
                    // Extraer solo el contenido del body
                    preg_match('/<div id="product-content">(.*?)<\/div>/s', $html, $matches);
                    if (isset($matches[1])) {
                        echo $matches[1];
                    }
                    
                    // Cargar los assets de Astro
                    preg_match_all('/<link.*?href="(.*?)".*?>/i', $html, $css_matches);
                    foreach ($css_matches[1] as $css) {
                        wp_enqueue_style('astro-css-' . md5($css), RPP_PLUGIN_URL . 'astro-app/dist' . $css);
                    }
                    
                    preg_match_all('/<script.*?src="(.*?)".*?><\/script>/i', $html, $js_matches);
                    foreach ($js_matches[1] as $js) {
                        wp_enqueue_script('astro-js-' . md5($js), RPP_PLUGIN_URL . 'astro-app/dist' . $js, [], null, true);
                    }
                } else {
                    // Fallback: mostrar contenido básico
                    ?>
                    <div class="woocommerce-error">
                        <p>La página del producto no está disponible en este momento.</p>
                    </div>
                    <?php
                }
                ?>
            <?php endif; ?>
            
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