// Shell de la app autenticada: menú lateral + cuerpo (main con transición) + footer.
// El sidebar solo se muestra con sesión; el contenido ocupa el resto del ancho.
import type { ReactNode } from 'react';
import Box from '@mui/material/Box';
import type { User } from '../../api/types';
import { AppSidebar } from './AppSidebar';
import { AppFooter } from './AppFooter';
import { PageTransition } from './PageTransition';

export interface AppLayoutProps {
  user: User | null;
  onLogout: () => void;
  children: ReactNode;
}

export function AppLayout({ user, onLogout, children }: AppLayoutProps) {
  return (
    <Box sx={{ display: 'flex', minHeight: '100vh', bgcolor: 'background.default' }}>
      {user !== null ? <AppSidebar user={user} onLogout={onLogout} /> : null}
      <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        <Box component="main" sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column' }}>
          <PageTransition>{children}</PageTransition>
        </Box>
        <AppFooter />
      </Box>
    </Box>
  );
}
