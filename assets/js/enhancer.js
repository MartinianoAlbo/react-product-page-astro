/**
 * Progressive Product Display - Enhancer
 * JavaScript minimalista para mejorar la experiencia sin afectar performance
 */

(function() {
  'use strict';
  
  // Verificar que estamos en una página de producto
  if (!document.body.classList.contains('ppd-enhanced')) {
      return;
  }
  
  // Configuración global
  const config = window.ppdConfig || {};
  
  /**
   * Utilidad para hacer peticiones AJAX
   */
  const ajax = {
      post: function(action, data = {}) {
          const formData = new FormData();
          formData.append('action', action);
          formData.append('nonce', config.nonce);
          
          Object.keys(data).forEach(key => {
              formData.append(key, data[key]);
          });
          
          return fetch(config.ajaxUrl, {
              method: 'POST',
              body: formData,
              credentials: 'same-origin'
          }).then(res => res.json());
      }
  };
  
  /**
   * Inicializar mejoras cuando el DOM esté listo
   */
  function init() {
      console.log('PPD Enhancer iniciado para producto:', config.productId);
      
      // Aquí añadiremos las mejoras progresivamente
      enhanceAddToCart();
      enhanceGallery();
  }
  
  /**
   * Mejorar botón Add to Cart con AJAX
   */
  function enhanceAddToCart() {
      const addToCartBtn = document.querySelector('.single_add_to_cart_button');
      if (!addToCartBtn) return;
      
      // Por ahora solo log, implementaremos AJAX en el siguiente tramo
      addToCartBtn.addEventListener('click', function(e) {
          console.log('Add to cart clicked - próximamente AJAX');
      });
  }
  
  /**
   * Mejorar galería de imágenes
   */
  function enhanceGallery() {
      const mainImage = document.querySelector('.woocommerce-product-gallery__image');
      if (!mainImage) return;
      
      // Por ahora solo log, implementaremos funcionalidad en el siguiente tramo
      console.log('Galería detectada - mejoras próximamente');
  }
  
  // Iniciar cuando el DOM esté listo
  if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
  } else {
      init();
  }
  
})();