// Contexto + hook del modo de color (separado del provider para no romper Fast Refresh).
import { createContext, use } from 'react';
import type { ColorMode } from './theme';

export interface ColorModeContextValue {
  mode: ColorMode;
  toggle: () => void;
  setMode: (mode: ColorMode) => void;
}

export const ColorModeContext = createContext<ColorModeContextValue | null>(null);

export function useColorMode(): ColorModeContextValue {
  const ctx = use(ColorModeContext);
  if (ctx === null) {
    throw new Error('useColorMode debe usarse dentro de <ColorModeProvider>');
  }
  return ctx;
}
