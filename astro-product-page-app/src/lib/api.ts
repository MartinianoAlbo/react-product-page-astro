// Cliente API para comunicarse con WordPress

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

class WordPressAPI {
  private baseUrl: string;
  private nonce: string;

  constructor() {
    // Configurar baseUrl según el entorno usando variables de entorno
    const wpApiUrl = import.meta.env.WP_API_URL || 'http://saphirus.local/wp-json';
    
    if (import.meta.env.DEV) {
      // En desarrollo, usar la URL del .env
      this.baseUrl = `${wpApiUrl}/rpp/v1`;
    } else if (typeof window !== 'undefined') {
      // En el cliente (navegador), usar URL directa
      this.baseUrl = `${window.location.origin}/wp-json/rpp/v1`;
    } else {
      // En el servidor, usar URL completa del WordPress desde .env
      this.baseUrl = `${wpApiUrl}/rpp/v1`;
    }
    
    // El nonce se pasará desde WordPress
    this.nonce = '';
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

    const response = await fetch(url, {
      ...options,
      headers,
      credentials: 'include', // Importante para cookies de WordPress
    });

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

  async getProductBySlug(slug: string): Promise<Product> {
    return this.fetch(`/product/slug/${slug}`);
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
}

// Singleton para usar en toda la app
export const api = new WordPressAPI();