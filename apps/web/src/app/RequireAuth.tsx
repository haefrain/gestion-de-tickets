// Guard de rutas privadas (overview.md §8): mientras se restaura la sesión, espera; sin sesión
// redirige a login; con rol insuficiente, al listado. Se reutiliza envolviendo cualquier ruta.
import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import Box from '@mui/material/Box';
import CircularProgress from '@mui/material/CircularProgress';
import { useAuth } from '../features/auth/auth-context';
import type { Role } from '../shared/api/types';
import { ROUTES } from './router/routes';

export function RequireAuth({ children, roles }: { children: ReactNode; roles?: Role[] }) {
  const { isAuthenticated, hasRole, initializing } = useAuth();
  const location = useLocation();

  if (initializing) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
        <CircularProgress aria-label="Cargando sesión" />
      </Box>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to={ROUTES.login} replace state={{ from: location.pathname }} />;
  }

  if (roles !== undefined && roles.length > 0 && !hasRole(...roles)) {
    return <Navigate to={ROUTES.tickets} replace />;
  }

  return <>{children}</>;
}
