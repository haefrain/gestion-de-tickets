// Envoltorio reutilizable para los tres estados de datos: loading / error / empty
// (overview.md §6). Evita repetir esta lógica en cada vista. a11y con regiones aria-live.

import type { ReactNode } from 'react';
import Box from '@mui/material/Box';
import Alert from '@mui/material/Alert';
import CircularProgress from '@mui/material/CircularProgress';
import Typography from '@mui/material/Typography';

export interface DataStateProps {
  loading?: boolean;
  error?: string | null;
  empty?: boolean;
  emptyMessage?: string;
  children: ReactNode;
}

export function DataState({
  loading = false,
  error = null,
  empty = false,
  emptyMessage = 'No hay resultados.',
  children,
}: DataStateProps) {
  if (loading) {
    return (
      <Box role="status" aria-live="polite" sx={{ display: 'flex', justifyContent: 'center', py: 6 }}>
        <CircularProgress aria-label="Cargando" />
      </Box>
    );
  }

  if (error !== null && error !== '') {
    return (
      <Alert severity="error" role="alert" sx={{ my: 2 }}>
        {error}
      </Alert>
    );
  }

  if (empty) {
    return (
      <Box sx={{ textAlign: 'center', py: 6, color: 'text.secondary' }}>
        <Typography>{emptyMessage}</Typography>
      </Box>
    );
  }

  return <>{children}</>;
}
