// Contexto + hook de notificaciones (Snackbar). Separado del provider para no romper Fast Refresh.
import { createContext, use } from 'react';

export type NotifySeverity = 'success' | 'info' | 'warning' | 'error';

export interface NotificationsContextValue {
  notify: (message: string, severity?: NotifySeverity) => void;
}

export const NotificationsContext = createContext<NotificationsContextValue | null>(null);

export function useNotify(): NotificationsContextValue {
  const ctx = use(NotificationsContext);
  if (ctx === null) {
    throw new Error('useNotify debe usarse dentro de <NotificationsProvider>');
  }
  return ctx;
}
