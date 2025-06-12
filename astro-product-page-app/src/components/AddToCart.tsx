import React, { useState, useEffect } from 'react';
import { api, type Product, type ProductVariation } from '@/lib/api';

interface AddToCartProps {
  product: Product;
}

export default function AddToCart({ product }: AddToCartProps) {
  const [quantity, setQuantity] = useState(1);
  const [selectedVariation, setSelectedVariation] = useState<ProductVariation | null>(null);
  const [selectedAttributes, setSelectedAttributes] = useState<Record<string, string>>({});
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [cartCount, setCartCount] = useState(0);

  // Configurar nonce al montar 
  useEffect(() => {
    api.setNonce((window as any).rppData.nonce);
    fetchCartCount();
  }, []);

  // Para productos variables, establecer variación por defecto
  useEffect(() => {
    if (product.type === 'variable' && product.variations.length > 0) {
      // Encontrar primera variación en stock
      const defaultVariation = product.variations.find(v => v.stock_status === 'instock') || product.variations[0];
      setSelectedVariation(defaultVariation);
      setSelectedAttributes(defaultVariation.attributes);
    }
  }, [product]);

  const fetchCartCount = async () => {
    try {
      const cart = await api.getCart();
      setCartCount(cart.count);
    } catch (error) {
      console.error('Error fetching cart:', error);
    }
  };

  const handleQuantityChange = (value: number) => {
    const newQuantity = Math.max(1, Math.min(value, product.stock_quantity || 999));
    setQuantity(newQuantity);
  };

  const handleAttributeChange = (attributeName: string, value: string) => {
    const newAttributes = { ...selectedAttributes, [attributeName]: value };
    setSelectedAttributes(newAttributes);

    // Encontrar variación que coincida con los atributos seleccionados
    if (product.type === 'variable') {
      const variation = product.variations.find(v => {
        return Object.entries(newAttributes).every(([key, val]) => {
          return v.attributes[key] === val;
        });
      });

      if (variation) {
        setSelectedVariation(variation);
      }
    }
  };

  const handleAddToCart = async () => {
    setIsLoading(true);
      console.log(product)
    setMessage(null);

    try {
      console.log(product)
      const variationId = selectedVariation?.id || 0;
      const result = await api.addToCart(
        product.id,
        quantity,
        variationId,
        selectedAttributes
      );

      if (result.success) {
        setMessage({ type: 'success', text: '¡Producto añadido al carrito!' });
        setCartCount(result.cart_count);
        
        // Disparar evento personalizado para actualizar el carrito en WordPress
        window.dispatchEvent(new CustomEvent('cart-updated', { 
          detail: { count: result.cart_count, total: result.cart_total } 
        }));

        // Limpiar mensaje después de 3 segundos
        setTimeout(() => setMessage(null), 3000);
      }
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error instanceof Error ? error.message : 'Error al añadir al carrito' 
      });
    } finally {
      setIsLoading(false);
    }
  };

  const currentPrice = selectedVariation?.price || product.price;
  const isInStock = selectedVariation 
    ? selectedVariation.stock_status === 'instock'
    : product.stock_status === 'instock';
  const canAddToCart = isInStock && !isLoading;

  return (
    <div className="rpp-add-to-cart">
      {/* Selector de variaciones */}
      {product.type === 'variable' && product.attributes.length > 0 && (
        <div className="rpp-mb-6 rpp-space-y-4">
          {product.attributes.map((attribute) => (
            <div key={attribute.id} className="rpp-variation-selector">
              <label className="rpp-block rpp-text-sm rpp-font-medium rpp-text-gray-700 rpp-mb-2">
                {attribute.name}
              </label>
              
              {/* Selector tipo dropdown */}
              <select
                className="rpp-w-full rpp-px-4 rpp-py-2 rpp-border rpp-border-gray-300 rpp-rounded-md focus:rpp-ring-2 focus:rpp-ring-primary-500 focus:rpp-border-transparent"
                value={selectedAttributes[attribute.name] || ''}
                onChange={(e) => handleAttributeChange(attribute.name, e.target.value)}
              >
                <option value="">Seleccionar {attribute.name}</option>
                {attribute.options.map((option: string) => (
                  <option key={option} value={option}>
                    {option}
                  </option>
                ))}
              </select>
            </div>
          ))}
        </div>
      )}

      {/* Cantidad y botón */}
      <div className="rpp-flex rpp-items-center rpp-gap-4 rpp-mb-4">
        {/* Selector de cantidad */}
        <div className="rpp-quantity-selector rpp-flex rpp-items-center rpp-border rpp-border-gray-300 rpp-rounded-md">
          <button
            type="button"
            className="rpp-px-3 rpp-py-2 hover:rpp-bg-gray-100 rpp-transition-colors"
            onClick={() => handleQuantityChange(quantity - 1)}
            disabled={quantity <= 1}
          >
            <svg className="rpp-w-4 rpp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
            </svg>
          </button>
          
          <input
            type="number"
            min="1"
            max={product.stock_quantity || 999}
            value={quantity}
            onChange={(e) => handleQuantityChange(parseInt(e.target.value) || 1)}
            className="rpp-w-16 rpp-text-center rpp-border-0 focus:rpp-ring-0"
          />
          
          <button
            type="button"
            className="rpp-px-3 rpp-py-2 hover:rpp-bg-gray-100 rpp-transition-colors"
            onClick={() => handleQuantityChange(quantity + 1)}
            disabled={product.stock_quantity ? quantity >= product.stock_quantity : false}
          >
            <svg className="rpp-w-4 rpp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>

        {/* Botón añadir al carrito */}
        <button
          type="button"
          onClick={handleAddToCart}
          // disabled={!canAddToCart}
          className={`
            rpp-flex-1 rpp-px-6 rpp-py-3 rpp-rounded-md rpp-font-medium rpp-transition-all
            ${canAddToCart
              ? 'rpp-bg-primary-600 rpp-text-white hover:rpp-bg-primary-700 rpp-shadow-lg hover:rpp-shadow-xl'
              : 'rpp-bg-gray-300 rpp-text-gray-500 rpp-cursor-not-allowed'
            }
          `}
        >
          {isLoading ? (
            <span className="rpp-flex rpp-items-center rpp-justify-center">
              <svg className="rpp-animate-spin rpp-h-5 rpp-w-5 rpp-mr-2" fill="none" viewBox="0 0 24 24">
                <circle className="rpp-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="rpp-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              Añadiendo...
            </span>
          ) : (
            <>
              <svg className="rpp-w-5 rpp-h-5 rpp-mr-2 rpp-inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Añadir al carrito
            </>
          )}
        </button>
      </div>


      {/* Mensajes */}
      {message && (
        <div className={`
          rpp-p-4 rpp-rounded-md rpp-mb-4 rpp-animate-fade-in
          ${message.type === 'success' 
            ? 'rpp-bg-green-50 rpp-text-green-800 rpp-border rpp-border-green-200' 
            : 'rpp-bg-red-50 rpp-text-red-800 rpp-border rpp-border-red-200'
          }
        `}>
          <div className="rpp-flex rpp-items-center">
            {message.type === 'success' ? (
              <svg className="rpp-w-5 rpp-h-5 rpp-mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
            ) : (
              <svg className="rpp-w-5 rpp-h-5 rpp-mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            )}
            <span>{message.text}</span>
          </div>
          
          {message.type === 'success' && (
            <div className="rpp-mt-2 rpp-flex rpp-gap-4">
              <a 
                href="/cart" 
                className="rpp-text-sm rpp-font-medium rpp-text-green-600 hover:rpp-text-green-800"
              >
                Ver carrito ({cartCount})
              </a>
              <a 
                href="/checkout" 
                className="rpp-text-sm rpp-font-medium rpp-text-green-600 hover:rpp-text-green-800"
              >
                Finalizar compra →
              </a>
            </div>
          )}
        </div>
      )}

      
    </div>
  );
}