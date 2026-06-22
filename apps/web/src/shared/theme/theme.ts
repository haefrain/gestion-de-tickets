// Tema único de la app (y de Storybook). Índigo claro/oscuro, tokens MUI (overview.md §2-3).
// createAppTheme(mode) es la ÚNICA fuente de verdad del tema: la usan AppProviders y preview.tsx.

import { createTheme, type Theme } from '@mui/material/styles';
import { indigo, pink } from '@mui/material/colors';

export type ColorMode = 'light' | 'dark';

export function createAppTheme(mode: ColorMode): Theme {
  const isLight = mode === 'light';
  return createTheme({
    palette: {
      mode,
      primary: { main: isLight ? indigo[600] : indigo[300] },
      secondary: { main: isLight ? pink[500] : pink[300] },
    },
    shape: { borderRadius: 8 },
    spacing: 8,
    typography: {
      fontFamily: 'Roboto, system-ui, -apple-system, "Segoe UI", sans-serif',
    },
  });
}
