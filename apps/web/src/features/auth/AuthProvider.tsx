// Gestiona la sesión: access token en memoria (apiClient) y datos de usuario en estado.
// En F3 el flujo real contra el backend está cableado pero la auth real llega en F6/L1;
// loginAsDemo permite recorrer la UI sin backend.
import { useCallback, useMemo, useState, type ReactNode } from 'react';
import { apiClient, setAccessToken } from '../../shared/api/apiClient';
import { ROLE_LABEL } from '../../shared/theme/tokens';
import type { Role, User } from '../../shared/api/types';
import { AuthContext, type AuthContextValue, type RegisterInput } from './auth-context';

interface LoginResponse {
  accessToken: string;
  user: User;
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

  const login = useCallback(async (email: string, password: string): Promise<void> => {
    const result = await apiClient.post<LoginResponse>('/login', { email, password });
    setAccessToken(result.accessToken);
    setUser(result.user);
  }, []);

  const register = useCallback(
    async (input: RegisterInput): Promise<void> => {
      await apiClient.post('/register', input);
      await login(input.email, input.password);
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
