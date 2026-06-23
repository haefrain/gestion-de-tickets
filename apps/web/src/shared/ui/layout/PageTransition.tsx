// Transición de entrada del contenido al cambiar de ruta (fade + leve desplazamiento).
// Respeta prefers-reduced-motion vía el tema (las animaciones se desactivan solas).
import type { ReactNode } from 'react';
import Box from '@mui/material/Box';
import { useLocation } from 'react-router-dom';

export function PageTransition({ children }: { children: ReactNode }) {
  const { pathname } = useLocation();

  return (
    <Box
      key={pathname}
      sx={{
        flexGrow: 1,
        display: 'flex',
        flexDirection: 'column',
        animation: 'pageIn .28s ease both',
        '@keyframes pageIn': {
          from: { opacity: 0, transform: 'translateY(10px)' },
          to: { opacity: 1, transform: 'translateY(0)' },
        },
        '@media (prefers-reduced-motion: reduce)': { animation: 'none' },
      }}
    >
      {children}
    </Box>
  );
}
