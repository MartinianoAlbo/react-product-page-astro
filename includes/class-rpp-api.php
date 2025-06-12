<?php
/**
 * Clase para manejar todos los endpoints de la API
 * 
 * @package React_Product_Page
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class RPP_API {
    
    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'rpp/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        
    }
    
    /**
     * Registrar todas las rutas de la API
     */
    public function register_routes() {
        // Endpoint para producto individual por ID
        register_rest_route(self::API_NAMESPACE, '/product/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
        
        // Endpoint para producto individual por slug
        register_rest_route(self::API_NAMESPACE, '/product/slug/(?P<slug>[a-zA-Z0-9-_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_by_slug'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => [
                    'validate_callback' => function($param) {
                        return !empty($param) && is_string($param);
                    },
                    'sanitize_callback' => 'sanitize_title'
                ]
            ]
        ]);
        
        // Endpoint para añadir al carrito
        register_rest_route(self::API_NAMESPACE, '/cart/add', [
            'methods' => 'POST',
            'callback' => [$this, 'add_to_cart'],
            'permission_callback' => [$this, 'check_nonce'],
            'args' => [
                'product_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ],
                'quantity' => [
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ],
                'variation_id' => [
                    'default' => 0,
                    'sanitize_callback' => 'absint'
                ],
                'variation' => [
                    'default' => [],
                    'sanitize_callback' => [$this, 'sanitize_variation_data']
                ]
            ]
        ]);
        
        // Endpoint para obtener el estado del carrito
        register_rest_route(self::API_NAMESPACE, '/cart', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cart'],
            'permission_callback' => '__return_true'
        ]);
        
        // Endpoint para productos relacionados
        register_rest_route(self::API_NAMESPACE, '/product/(?P<id>\d+)/related', [
            'methods' => 'GET',
            'callback' => [$this, 'get_related_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ],
                'limit' => [
                    'default' => 4,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 20;
                    },
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
        
        // Endpoint para reviews del producto
        register_rest_route(self::API_NAMESPACE, '/product/(?P<id>\d+)/reviews', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_reviews'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint'
                ],
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ],
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
    }
    
    /**
     * Obtener datos completos del producto
     */
    public function get_product($request) {
        $product_id = $request['id'];
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('product_not_found', 'Producto no encontrado', ['status' => 404]);
        }
        
        // Preparar respuesta
        $data = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'featured' => $product->is_featured(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'purchasable' => $product->is_purchasable(),
            'total_sales' => $product->get_total_sales(),
            'virtual' => $product->is_virtual(),
            'downloadable' => $product->is_downloadable(),
            'external_url' => $product->is_type('external') ? $product->get_product_url() : '',
            'button_text' => $product->is_type('external') ? $product->get_button_text() : '',
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'backorders' => $product->get_backorders(),
            'sold_individually' => $product->is_sold_individually(),
            'weight' => $product->get_weight(),
            'dimensions' => [
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height()
            ],
            'shipping_class' => $product->get_shipping_class(),
            'shipping_class_id' => $product->get_shipping_class_id(),
            'reviews_allowed' => $product->get_reviews_allowed(),
            'average_rating' => $product->get_average_rating(),
            'rating_count' => $product->get_rating_count(),
            'review_count' => $product->get_review_count(),
            'related_ids' => $product->get_related(),
            'upsell_ids' => $product->get_upsell_ids(),
            'cross_sell_ids' => $product->get_cross_sell_ids(),
            'categories' => $this->get_product_terms($product, 'product_cat'),
            'tags' => $this->get_product_terms($product, 'product_tag'),
            'images' => $this->get_product_images($product),
            'attributes' => $this->get_product_attributes($product),
            'default_attributes' => $product->get_default_attributes(),
            'variations' => [],
            'grouped_products' => [],
            'menu_order' => $product->get_menu_order(),
            'meta_data' => $this->get_product_meta($product)
        ];
        
        // Datos específicos según el tipo de producto
        if ($product->is_type('variable')) {
            $data['variations'] = $this->get_product_variations($product);
        } elseif ($product->is_type('grouped')) {
            $data['grouped_products'] = $product->get_children();
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Obtener producto por slug
     */
    public function get_product_by_slug($request) {
        $slug = $request['slug'];
        
        // Buscar producto por slug
        $products = get_posts([
            'name' => $slug,
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        
        if (empty($products)) {
            return new WP_Error('product_not_found', 'Producto no encontrado', ['status' => 404]);
        }
        
        $product = wc_get_product($products[0]->ID);
        
        if (!$product) {
            return new WP_Error('product_not_found', 'Producto no encontrado', ['status' => 404]);
        }
        
        // Reutilizar la misma lógica del método get_product
        $data = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'featured' => $product->is_featured(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'purchasable' => $product->is_purchasable(),
            'total_sales' => $product->get_total_sales(),
            'virtual' => $product->is_virtual(),
            'downloadable' => $product->is_downloadable(),
            'external_url' => $product->is_type('external') ? $product->get_product_url() : '',
            'button_text' => $product->is_type('external') ? $product->get_button_text() : '',
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'backorders' => $product->get_backorders(),
            'sold_individually' => $product->is_sold_individually(),
            'weight' => $product->get_weight(),
            'dimensions' => [
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height()
            ],
            'shipping_class' => $product->get_shipping_class(),
            'shipping_class_id' => $product->get_shipping_class_id(),
            'reviews_allowed' => $product->get_reviews_allowed(),
            'average_rating' => $product->get_average_rating(),
            'rating_count' => $product->get_rating_count(),
            'review_count' => $product->get_review_count(),
            'related_ids' => $product->get_related(),
            'upsell_ids' => $product->get_upsell_ids(),
            'cross_sell_ids' => $product->get_cross_sell_ids(),
            'categories' => $this->get_product_terms($product, 'product_cat'),
            'tags' => $this->get_product_terms($product, 'product_tag'),
            'images' => $this->get_product_images($product),
            'attributes' => $this->get_product_attributes($product),
            'default_attributes' => $product->get_default_attributes(),
            'variations' => [],
            'grouped_products' => [],
            'menu_order' => $product->get_menu_order(),
            'meta_data' => $this->get_product_meta($product)
        ];
        
        // Datos específicos según el tipo de producto
        if ($product->is_type('variable')) {
            $data['variations'] = $this->get_product_variations($product);
        } elseif ($product->is_type('grouped')) {
            $data['grouped_products'] = $product->get_children();
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Añadir producto al carrito
     */
    public function add_to_cart($request) {
        $product_id = $request['product_id'];
        $quantity = $request['quantity'];
        $variation_id = $request['variation_id'];
        $variation = $request['variation'];
        
        // Verificar que WooCommerce esté inicializado
        if (!function_exists('WC') || !WC()->cart) {
            return new WP_Error('cart_not_initialized', 'El carrito no está inicializado', ['status' => 500]);
        }
        
        // Intentar añadir al carrito
        try {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
            
            if ($cart_item_key) {
                return rest_ensure_response([
                    'success' => true,
                    'cart_item_key' => $cart_item_key,
                    'cart_total' => WC()->cart->get_cart_total(),
                    'cart_count' => WC()->cart->get_cart_contents_count(),
                    'message' => 'Producto añadido al carrito'
                ]);
            } else {
                return new WP_Error('add_to_cart_failed', 'No se pudo añadir el producto al carrito', ['status' => 400]);
            }
        } catch (Exception $e) {
            return new WP_Error('add_to_cart_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    /**
     * Obtener estado actual del carrito
     */
    public function get_cart($request) {
        if (!function_exists('WC') || !WC()->cart) {
            return rest_ensure_response([
                'items' => [],
                'total' => 0,
                'count' => 0
            ]);
        }
        
        $cart_items = [];
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = [
                'key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'variation_id' => $cart_item['variation_id'],
                'quantity' => $cart_item['quantity'],
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'total' => $cart_item['line_total'],
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
            ];
        }
        
        return rest_ensure_response([
            'items' => $cart_items,
            'total' => WC()->cart->get_cart_total(),
            'count' => WC()->cart->get_cart_contents_count(),
            'needs_shipping' => WC()->cart->needs_shipping(),
            'coupons' => WC()->cart->get_applied_coupons()
        ]);
    }
    
    /**
     * Obtener productos relacionados
     */
    public function get_related_products($request) {
        $product_id = $request['id'];
        $limit = $request['limit'];
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('product_not_found', 'Producto no encontrado', ['status' => 404]);
        }
        
        $related_ids = wc_get_related_products($product_id, $limit);
        $related_products = [];
        
        foreach ($related_ids as $related_id) {
            $related_product = wc_get_product($related_id);
            if ($related_product) {
                $related_products[] = [
                    'id' => $related_product->get_id(),
                    'name' => $related_product->get_name(),
                    'price' => $related_product->get_price(),
                    'price_html' => $related_product->get_price_html(),
                    'image' => wp_get_attachment_image_url($related_product->get_image_id(), 'woocommerce_thumbnail'),
                    'permalink' => $related_product->get_permalink()
                ];
            }
        }
        
        return rest_ensure_response($related_products);
    }
    
    /**
     * Obtener reviews del producto
     */
    public function get_product_reviews($request) {
        $product_id = $request['id'];
        $page = $request['page'];
        $per_page = $request['per_page'];
        
        $args = [
            'post_id' => $product_id,
            'status' => 'approve',
            'type' => 'review',
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page
        ];
        
        $reviews = get_comments($args);
        $total_reviews = get_comments([
            'post_id' => $product_id,
            'status' => 'approve',
            'type' => 'review',
            'count' => true
        ]);
        
        $reviews_data = [];
        foreach ($reviews as $review) {
            $reviews_data[] = [
                'id' => $review->comment_ID,
                'author' => $review->comment_author,
                'date' => $review->comment_date,
                'content' => $review->comment_content,
                'rating' => get_comment_meta($review->comment_ID, 'rating', true),
                'verified' => wc_review_is_from_verified_owner($review->comment_ID)
            ];
        }
        
        return rest_ensure_response([
            'reviews' => $reviews_data,
            'total' => $total_reviews,
            'pages' => ceil($total_reviews / $per_page),
            'current_page' => $page
        ]);
    }
    
    /**
     * Verificar nonce para endpoints protegidos
     */
    public function check_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        return wp_verify_nonce($nonce, 'wp_rest');
    }
    
    /**
     * Obtener imágenes del producto
     */
    private function get_product_images($product) {
        $images = [];
        
        // Imagen principal
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $images[] = $this->format_image_data($main_image_id, true);
        }
        
        // Galería
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $image_id) {
            $images[] = $this->format_image_data($image_id, false);
        }
        
        return $images;
    }
    
    /**
     * Formatear datos de imagen
     */
    private function format_image_data($image_id, $is_main = false) {
        return [
            'id' => $image_id,
            'src' => wp_get_attachment_image_url($image_id, 'full'),
            'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
            'srcset' => wp_get_attachment_image_srcset($image_id),
            'sizes' => wp_get_attachment_image_sizes($image_id),
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            'title' => get_the_title($image_id),
            'is_main' => $is_main
        ];
    }
    
    /**
     * Obtener términos del producto (categorías o tags)
     */
    private function get_product_terms($product, $taxonomy) {
        $terms = get_the_terms($product->get_id(), $taxonomy);
        $terms_data = [];
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $terms_data[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                    'count' => $term->count
                ];
            }
        }
        
        return $terms_data;
    }
    
    /**
     * Obtener atributos del producto
     */
    private function get_product_attributes($product) {
        $attributes = $product->get_attributes();
        $attributes_data = [];
        
        foreach ($attributes as $attribute) {
            $attributes_data[] = [
                'id' => $attribute->get_id(),
                'name' => $attribute->get_name(),
                'position' => $attribute->get_position(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
                'options' => $attribute->get_options()
            ];
        }
        
        return $attributes_data;
    }
    
    /**
     * Obtener variaciones del producto
     */
    private function get_product_variations($product) {
        $variations = [];
        $available_variations = $product->get_available_variations();
        
        foreach ($available_variations as $variation_data) {
            $variation = wc_get_product($variation_data['variation_id']);
            if (!$variation) continue;
            
            $variations[] = [
                'id' => $variation->get_id(),
                'sku' => $variation->get_sku(),
                'price' => $variation->get_price(),
                'regular_price' => $variation->get_regular_price(),
                'sale_price' => $variation->get_sale_price(),
                'price_html' => $variation->get_price_html(),
                'on_sale' => $variation->is_on_sale(),
                'purchasable' => $variation->is_purchasable(),
                'visible' => $variation->is_visible(),
                'virtual' => $variation->is_virtual(),
                'downloadable' => $variation->is_downloadable(),
                'stock_quantity' => $variation->get_stock_quantity(),
                'stock_status' => $variation->get_stock_status(),
                'image' => $variation_data['image'],
                'attributes' => $variation->get_variation_attributes(),
                'weight' => $variation->get_weight(),
                'dimensions' => [
                    'length' => $variation->get_length(),
                    'width' => $variation->get_width(),
                    'height' => $variation->get_height()
                ]
            ];
        }
        
        return $variations;
    }
    
    /**
     * Obtener meta datos del producto
     */
    private function get_product_meta($product) {
        // Aquí puedes añadir cualquier meta personalizada que necesites
        return [
            'total_sales' => get_post_meta($product->get_id(), 'total_sales', true),
            // Añade más meta fields según necesites
        ];
    }
    
    /**
     * Sanitizar datos de variación
     */
    private function sanitize_variation_data($data) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[sanitize_text_field($key)] = sanitize_text_field($value);
        }
        
        return $sanitized;
    }
}