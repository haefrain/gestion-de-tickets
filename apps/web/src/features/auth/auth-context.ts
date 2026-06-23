// Contexto + hook de autenticación (separado del provider para no romper Fast Refresh).
import { createContext, use } from 'react';
import type { Role, User } from '../../shared/api/types';

export interface RegisterInput {
  name: string;
  email: string;
  password: string;
}

export interface AuthContextValue {
  user: User | null;
  isAuthenticated: boolean;
  /** True mientras se intenta restaurar la sesión al cargar la app (las rutas privadas esperan). */
  initializing: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (input: RegisterInput) => Promise<void>;
  logout: () => void;
  /** Acceso rápido por rol con las credenciales sembradas (make seed). Solo para la demo local. */
  loginAsDemo: (role: Role) => Promise<void>;
  hasRole: (...roles: Role[]) => boolean;
}

export const AuthContext = createContext<AuthContextValue | null>(null);

export function useAuth(): AuthContextValue {
  const ctx = use(AuthContext);
  if (ctx === null) {
    throw new Error('useAuth debe usarse dentro de <AuthProvider>');
  }
  return ctx;
}
