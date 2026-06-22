// Provee toasts (Snackbar + Alert con aria-live) para resultados de acciones (overview.md §6).
import { useMemo, useRef, useState, type ReactNode } from 'react';
import Snackbar from '@mui/material/Snackbar';
import Alert from '@mui/material/Alert';
import {
  NotificationsContext,
  type NotificationsContextValue,
  type NotifySeverity,
} from './notifications-context';

interface Notification {
  message: string;
  severity: NotifySeverity;
  key: number;
}

export function NotificationsProvider({ children }: { children: ReactNode }) {
  const [current, setCurrent] = useState<Notification | null>(null);
  const [open, setOpen] = useState(false);
  const counter = useRef(0);

  const value = useMemo<NotificationsContextValue>(
    () => ({
      notify: (message, severity = 'info') => {
        counter.current += 1;
        setCurrent({ message, severity, key: counter.current });
        setOpen(true);
      },
    }),
    [],
  );

  return (
    <NotificationsContext value={value}>
      {children}
      <Snackbar
        key={current?.key}
        open={open}
        autoHideDuration={5000}
        onClose={(_event, reason) => {
          if (reason !== 'clickaway') {
            setOpen(false);
          }
        }}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        {current !== null ? (
          <Alert
            severity={current.severity}
            variant="filled"
            onClose={() => setOpen(false)}
            sx={{ width: '100%' }}
          >
            {current.message}
          </Alert>
        ) : undefined}
      </Snackbar>
    </NotificationsContext>
  );
}
