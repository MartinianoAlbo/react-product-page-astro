import { useState } from 'react';
import type { ProductImage } from '@/lib/api';

export default function Gallery({ mainImage, images, productName }: { mainImage: ProductImage, images: ProductImage[], productName: string }) {
  const [active, setActive] = useState(mainImage.id);
  const activeImage = images.find(img => img.id === active) || mainImage;

  return (
    <div className="rpp-gallery rpp-px-20" role="region" aria-label="Product image gallery">
      {/* Imagen principal */}
      <div className="rpp-main rpp-relative rpp-overflow-hidden rpp-rounded-lg rpp-bg-gray-100">
        <img
          src={activeImage.src}
          alt={activeImage.alt || productName}
          className="rpp-w-full rpp-h-auto rpp-object-cover"
          loading="eager"
          style={{ aspectRatio: '1 / 1' }}
        />
      </div>

      {/* Thumbnails */}
      {images.length > 1 && (
        <div className="rpp-thumbs rpp-grid rpp-grid-cols-4 rpp-gap-2 rpp-mt-4">
          {images.map(img => (
            <button
              key={img.id}
              onClick={() => setActive(img.id)}
              aria-pressed={img.id === active}
              className={`
                rpp-relative rpp-aspect-square rpp-overflow-hidden rpp-rounded-md rpp-border-2 rpp-transition-all
                ${img.id === active 
                  ? 'rpp-border-primary-500 rpp-ring-2 rpp-ring-primary-200' 
                  : 'rpp-border-gray-200 hover:rpp-border-gray-300'
                }
              `}
            >
              <img
                src={img.thumbnail || img.src}
                alt=""
                className="rpp-w-full rpp-h-full rpp-object-cover"
                loading="lazy"
              />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
