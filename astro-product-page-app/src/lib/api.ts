// Cliente API para comunicarse con WordPress
import https from 'https';

export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  short_description: string;
  price: string;
  regular_price: string;
  sale_price: string;
  price_html: string;
  on_sale: boolean;
  stock_status: string;
  stock_quantity: number | null;
  images: ProductImage[];
  categories: ProductCategory[];
  attributes: any[];
  variations: ProductVariation[];
  type: string;
  average_rating: string;
  rating_count: number;
  review_count: number;
}

export interface ProductImage {
  id: number;
  src: string;
  thumbnail: string;
  srcset?: string;
  sizes?: string;
  alt: string;
  title: string;
  is_main: boolean;
}

export interface ProductCategory {
  id: number;
  name: string;
  slug: string;
}

export interface ProductVariation {
  id: number;
  price: string;
  regular_price: string;
  sale_price: string;
  price_html: string;
  attributes: Record<string, string>;
  stock_status: string;
  image: any;
}

export interface CartItem {
  key: string;
  product_id: number;
  variation_id: number;
  quantity: number;
  name: string;
  price: string;
  total: string;
  image: string;
}

export interface Cart {
  items: CartItem[];
  total: string;
  count: number;
  needs_shipping: boolean;
  coupons: string[];
}

const WP_API_URL        = import.meta.env.WP_API_URL as string;          // p.ej. 'http://saphirus.local/wp-json'
const WC_CONSUMER_KEY   = import.meta.env.WC_CONSUMER_KEY as string;    // tu CK
const WC_CONSUMER_SECRET= import.meta.env.WC_CONSUMER_SECRET as string; // tu CS
const devAgent = new https.Agent({ rejectUnauthorized: false });

class WordPressAPI {
  private baseUrl: string;
  private nonce: string;
  

  constructor() {
    this.baseUrl = `${WP_API_URL}/rpp/v1`;
    this.nonce   = '';
  }

  setNonce(nonce: string) {
    this.nonce = nonce;
  }

  private async fetch(endpoint: string, options: RequestInit = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    
    const headers = new Headers(options.headers || {});
    headers.set('Content-Type', 'application/json');
    
    if (this.nonce) {
      headers.set('X-WP-Nonce', this.nonce);
    }

    const fetchOptions: RequestInit & { agent?: https.Agent } = {
      ...options,
      headers,
      credentials: 'include',
    };

    if (import.meta.env.SSR && process.env.NODE_ENV === 'development') {
      console.log('dev');
      process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
    }

    const response = await fetch(url, fetchOptions);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
  }

  // Productos
  async getProduct(id: number): Promise<Product> {
    return this.fetch(`/product/${id}`);
  }

  

  async getRelatedProducts(id: number, limit: number = 4) {
    return this.fetch(`/product/${id}/related?limit=${limit}`);
  }

  async getProductReviews(id: number, page: number = 1, perPage: number = 10) {
    return this.fetch(`/product/${id}/reviews?page=${page}&per_page=${perPage}`);
  }

  // Carrito
  async addToCart(productId: number, quantity: number = 1, variationId: number = 0, variation: Record<string, string> = {}) {
    return this.fetch('/cart/add', {
      method: 'POST',
      body: JSON.stringify({
        product_id: productId,
        quantity,
        variation_id: variationId,
        variation
      })
    });
  }

  async getCart(): Promise<Cart> {
    return this.fetch('/cart');
  }

  async getProductBySlug(slug: string): Promise<Product> {
    return this.fetch(`/product/slug/${slug}`);
  }

  async getProductBySlugDirect(slug: string): Promise<Product> {
    // Construye la URL añadiendo consumer_key y consumer_secret
    const url = new URL(`${WP_API_URL}/wc/v3/products`);
    url.searchParams.set('slug', slug);
    url.searchParams.set('consumer_key', WC_CONSUMER_KEY);
    url.searchParams.set('consumer_secret', WC_CONSUMER_SECRET);

    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(`WooCommerce API error: ${res.status}`);
    const [product] = await res.json();
    return product;
  }
  
}

// Singleton para usar en toda la app
export const api = new WordPressAPI();