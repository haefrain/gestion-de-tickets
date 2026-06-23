// Gestiona la sesión: access token en memoria (apiClient) y datos de usuario en estado.
// El login (HU-L1-E1-02) devuelve solo tokens; el User de la sesión se deriva del JWT
// (ver session.ts) hasta que exista GET /api/v1/me (HU-L1-E3-01).
// loginAsDemo permite recorrer la UI sin backend.
import { useCallback, useMemo, useState, type ReactNode } from 'react';
import { apiClient, setAccessToken } from '../../shared/api/apiClient';
import { ROLE_LABEL } from '../../shared/theme/tokens';
import type { Role, User } from '../../shared/api/types';
import { AuthContext, type AuthContextValue, type RegisterInput } from './auth-context';
import { sessionUserFromToken } from './session';

/** Contrato real de POST /api/v1/login (docs/product/legends/L1-identidad.md §HU-L1-E1-02). */
interface LoginResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
}

function demoUser(role: Role): User {
  return {
    id: `demo-${role}`,
    name: `${ROLE_LABEL[role]} Demo`,
    email: `${role}@tickets.local`,
    role,
  };
}

export function AuthProvider({
  children,
  initialUser = null,
}: {
  children: ReactNode;
  initialUser?: User | null;
}) {
  const [user, setUser] = useState<User | null>(initialUser);

  const login = useCallback(async (email: string, password: string, name?: string): Promise<void> => {
    const result = await apiClient.post<LoginResponse>('/login', { email, password });
    setAccessToken(result.access_token);
    setUser(sessionUserFromToken({ accessToken: result.access_token, email, name }));
  }, []);

  const register = useCallback(
    async (input: RegisterInput): Promise<void> => {
      await apiClient.post('/register', input);
      // Reusa el name tecleado para la sesión (el login no lo devuelve hasta /me).
      await login(input.email, input.password, input.name);
    },
    [login],
  );

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isAuthenticated: user !== null,
      login,
      register,
      logout: () => {
        setAccessToken(null);
        setUser(null);
      },
      loginAsDemo: (role: Role) => setUser(demoUser(role)),
      hasRole: (...roles: Role[]) => user !== null && roles.includes(user.role),
    }),
    [user, login, register],
  );

  return <AuthContext value={value}>{children}</AuthContext>;
}
