// Composición única de providers de la app: tema, datos (Query), sesión y notificaciones.
import type { ReactNode } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { ColorModeProvider } from '../../shared/theme/ColorModeProvider';
import { AuthProvider } from '../../features/auth/AuthProvider';
import { NotificationsProvider } from '../../shared/ui/feedback/NotificationsProvider';
import { createQueryClient } from './queryClient';

const queryClient = createQueryClient();

export function AppProviders({ children }: { children: ReactNode }) {
  return (
    <ColorModeProvider>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <NotificationsProvider>{children}</NotificationsProvider>
        </AuthProvider>
      </QueryClientProvider>
    </ColorModeProvider>
  );
}
