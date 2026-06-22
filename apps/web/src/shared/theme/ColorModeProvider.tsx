// Provee el tema MUI + toggle claro/oscuro persistido en sesión (overview.md §3).
import { useMemo, useState, type ReactNode } from 'react';
import { ThemeProvider } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import { createAppTheme, type ColorMode } from './theme';
import { ColorModeContext, type ColorModeContextValue } from './color-mode-context';

const STORAGE_KEY = 'tickets.colorMode';

function readInitialMode(): ColorMode {
  if (typeof window === 'undefined') {
    return 'light';
  }
  const stored = window.sessionStorage.getItem(STORAGE_KEY);
  return stored === 'dark' || stored === 'light' ? stored : 'light';
}

export function ColorModeProvider({
  children,
  initialMode,
}: {
  children: ReactNode;
  /** Modo inicial explícito (lo usa Storybook desde el toolbar); por defecto, sesión. */
  initialMode?: ColorMode;
}) {
  const [mode, setModeState] = useState<ColorMode>(initialMode ?? readInitialMode);

  const value = useMemo<ColorModeContextValue>(() => {
    const setMode = (next: ColorMode): void => {
      setModeState(next);
      window.sessionStorage.setItem(STORAGE_KEY, next);
    };
    return { mode, setMode, toggle: () => setMode(mode === 'light' ? 'dark' : 'light') };
  }, [mode]);

  const theme = useMemo(() => createAppTheme(mode), [mode]);

  return (
    <ColorModeContext value={value}>
      <ThemeProvider theme={theme}>
        <CssBaseline />
        {children}
      </ThemeProvider>
    </ColorModeContext>
  );
}
