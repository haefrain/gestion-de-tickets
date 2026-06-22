// Guard de rutas privadas (overview.md §8): sin sesión redirige a login; con rol insuficiente,
// al listado. Se reutiliza envolviendo cualquier ruta protegida.
import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../features/auth/auth-context';
import type { Role } from '../shared/api/types';
import { ROUTES } from './router/routes';

export function RequireAuth({ children, roles }: { children: ReactNode; roles?: Role[] }) {
  const { isAuthenticated, hasRole } = useAuth();
  const location = useLocation();

  if (!isAuthenticated) {
    return <Navigate to={ROUTES.login} replace state={{ from: location.pathname }} />;
  }

  if (roles !== undefined && roles.length > 0 && !hasRole(...roles)) {
    return <Navigate to={ROUTES.tickets} replace />;
  }

  return <>{children}</>;
}
