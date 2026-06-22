/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Base URL de la API. Por defecto el proxy `/api/v1`. */
  readonly VITE_API_URL?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
