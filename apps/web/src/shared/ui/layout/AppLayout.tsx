// Shell reutilizable de la app: Header + cuerpo (main) + Footer. TODAS las vistas lo reutilizan.
// El footer queda anclado abajo (columna flex de alto mínimo 100vh).
import type { ReactNode } from 'react';
import Box from '@mui/material/Box';
import type { User } from '../../api/types';
import { AppHeader } from './AppHeader';
import { AppFooter } from './AppFooter';

export interface AppLayoutProps {
  user: User | null;
  onLogout: () => void;
  children: ReactNode;
}

export function AppLayout({ user, onLogout, children }: AppLayoutProps) {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <AppHeader user={user} onLogout={onLogout} />
      <Box component="main" sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column' }}>
        {children}
      </Box>
      <AppFooter />
    </Box>
  );
}
