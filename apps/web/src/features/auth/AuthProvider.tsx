// Gestiona la sesión: access token en memoria (apiClient) y datos del usuario en estado.
// Al montar intenta restaurar la sesión con la cookie de refresh (HU-L1-E1-03) y, si lo logra,
// carga el perfil real de GET /api/v1/me (HU-L1-E3-01). Login y registro siguen el mismo camino.
// loginAsDemo entra con las credenciales sembradas (make seed): acceso rápido por rol en la demo.
import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
import { apiClient, refreshSession, setAccessToken, setOnUnauthorized } from '../../shared/api/apiClient';
import type { Role, User } from '../../shared/api/types';
import { AuthContext, type AuthContextValue, type RegisterInput } from './auth-context';
import { displayNameFromEmail, roleFromTokenRoles } from './session';

/** Contrato de POST /api/v1/login (HU-L1-E1-02): solo tokens, sin perfil. */
interface LoginResponse {
  access_token: string;
  expires_in: number;
}

/** Contrato de GET /api/v1/me (HU-L1-E3-01). */
interface ProfileResponse {
  id: string;
  email: string;
  name: string | null;
  roles: string[];
}

/** Credenciales sembradas por `make seed` (SeedDemoCommand): acceso rápido por rol en la demo. */
const DEMO_LOGINS: Record<Role, { email: string; password: string }> = {
  client: { email: 'cliente@demo.local', password: 'Demo1234' },
  agent: { email: 'agente@demo.local', password: 'Demo1234' },
  admin: { email: 'admin@demo.local', password: 'Demo1234' },
};

function userFromProfile(profile: ProfileResponse): User {
  return {
    id: profile.id,
    name: profile.name?.trim() ? profile.name : displayNameFromEmail(profile.email),
    email: profile.email,
    role: roleFromTokenRoles(profile.roles),
  };
}

export function AuthProvider({
  children,
  initialUser = null,
  restoreSession = true,
}: {
  children: ReactNode;
  initialUser?: User | null;
  /** Permite a los tests evitar el intento de restauración de sesión al montar. */
  restoreSession?: boolean;
}) {
  const [user, setUser] = useState<User | null>(initialUser);
  // Mientras se intenta restaurar la sesión, las rutas privadas esperan (evita el parpadeo a login).
  const [initializing, setInitializing] = useState(restoreSession && initialUser === null);

  const login = useCallback(async (email: string, password: string): Promise<void> => {
    const result = await apiClient.post<LoginResponse>('/login', { email, password });
    setAccessToken(result.access_token);
    const profile = await apiClient.get<ProfileResponse>('/me');
    setUser(userFromProfile(profile));
  }, []);

  const register = useCallback(
    async (input: RegisterInput): Promise<void> => {
      await apiClient.post('/register', input);
      await login(input.email, input.password);
    },
    [login],
  );

  const loginAsDemo = useCallback(
    (role: Role): Promise<void> => {
      const { email, password } = DEMO_LOGINS[role];
      return login(email, password);
    },
    [login],
  );

  // Sesión expirada/ausente detectada por el apiClient (refresh fallido): limpiar y volver a login.
  useEffect(() => {
    setOnUnauthorized(() => {
      setAccessToken(null);
      setUser(null);
    });
    return () => setOnUnauthorized(null);
  }, []);

  // Restauración al montar: si hay cookie de refresh válida, recupera token + perfil real.
  useEffect(() => {
    if (!restoreSession || initialUser !== null) {
      return;
    }
    let active = true;
    void (async () => {
      try {
        if (await refreshSession()) {
          const profile = await apiClient.get<ProfileResponse>('/me');
          if (active) {
            setUser(userFromProfile(profile));
          }
        }
      } catch {
        // Sin sesión restaurable: el usuario queda deslogueado.
      } finally {
        if (active) {
          setInitializing(false);
        }
      }
    })();
    return () => {
      active = false;
    };
  }, [restoreSession, initialUser]);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isAuthenticated: user !== null,
      initializing,
      login,
      register,
      logout: () => {
        setAccessToken(null);
        setUser(null);
      },
      loginAsDemo,
      hasRole: (...roles: Role[]) => user !== null && roles.includes(user.role),
    }),
    [user, initializing, login, register, loginAsDemo],
  );

  return <AuthContext value={value}>{children}</AuthContext>;
}
