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
  login: (email: string, password: string) => Promise<void>;
  register: (input: RegisterInput) => Promise<void>;
  logout: () => void;
  /** Acceso de demostración sin backend (F3). El login real con JWT llega en F6/L1. */
  loginAsDemo: (role: Role) => void;
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
