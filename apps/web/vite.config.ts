/// <reference types="vitest/config" />
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    // Proxy de la API al backend (servicio api-nginx en la red Docker). El apiClient usa /api/v1.
    proxy: {
      '/api': { target: 'http://api-nginx:80', changeOrigin: true },
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
    css: true,
    // MUI importa react-transition-group con un "directory import" que el resolver ESM
    // de Node rechaza; inlinarlo hace que Vite lo resuelva.
    server: {
      deps: {
        inline: [/@mui\//, /react-transition-group/],
      },
    },
  },
});
