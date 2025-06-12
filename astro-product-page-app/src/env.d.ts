/// <reference types="astro/client" />

// Tipos globales para la integración con WordPress
interface Window {
  api: {
    setNonce: (nonce: string) => void;
  };
  ppdConfig?: {
    ajaxUrl: string;
    nonce: string;
    productId: number;
  };
}

// Variables de entorno
interface ImportMetaEnv {
  readonly PUBLIC_WP_URL: string;
  readonly PUBLIC_API_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}