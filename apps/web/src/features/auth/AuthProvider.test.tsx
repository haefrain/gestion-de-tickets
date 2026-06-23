// El AuthProvider mantiene la sesión contra el backend real: login/registro pueblan el user desde
// GET /me (HU-L1-E3-01) y, al montar, restaura la sesión con la cookie de refresh (HU-L1-E1-03).
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { AuthProvider } from './AuthProvider';
import { useAuth } from './auth-context';
import { setAccessToken } from '../../shared/api/apiClient';

const PROFILE = {
  id: '019ef235-6ffb-7e9c-ae73-f575fc1e1993',
  email: 'cliente@iatsae.test',
  name: 'Cliente Real',
  roles: ['ROLE_CLIENT'],
};

function json(body: unknown, status: number): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

/** Backend simulado; `overrides` permite cambiar la respuesta de una ruta por test. */
function mockBackend(overrides: Record<string, () => Response> = {}): void {
  vi.stubGlobal(
    'fetch',
    vi.fn((url: string | URL) => {
      const path = String(url);
      for (const [suffix, make] of Object.entries(overrides)) {
        if (path.endsWith(suffix)) {
          return Promise.resolve(make());
        }
      }
      if (path.endsWith('/register')) {
        return Promise.resolve(json({ id: PROFILE.id, email: PROFILE.email, roles: PROFILE.roles }, 201));
      }
      if (path.endsWith('/login')) {
        return Promise.resolve(json({ access_token: 'tok', refresh_token: 'op', expires_in: 900 }, 200));
      }
      if (path.endsWith('/me')) {
        return Promise.resolve(json(PROFILE, 200));
      }
      if (path.endsWith('/token/refresh')) {
        return Promise.resolve(json({}, 401));
      }
      return Promise.resolve(json({}, 404));
    }),
  );
}

function Harness() {
  const { user, isAuthenticated, register, login } = useAuth();
  return (
    <div>
      <button
        onClick={() =>
          void register({ name: 'Cliente Real', email: 'cliente@iatsae.test', password: 'Sup3rS3cret!' })
        }
      >
        registrar
      </button>
      <button onClick={() => void login('cliente@iatsae.test', 'Sup3rS3cret!')}>entrar</button>
      <div data-testid="auth">{isAuthenticated ? 'si' : 'no'}</div>
      <div data-testid="id">{user?.id ?? ''}</div>
      <div data-testid="email">{user?.email ?? ''}</div>
      <div data-testid="role">{user?.role ?? ''}</div>
      <div data-testid="name">{user?.name ?? ''}</div>
    </div>
  );
}

describe('AuthProvider', () => {
  beforeEach(() => setAccessToken(null));
  afterEach(() => vi.unstubAllGlobals());

  it('puebla la sesión desde /me tras un login', async () => {
    mockBackend();
    render(
      <AuthProvider restoreSession={false}>
        <Harness />
      </AuthProvider>,
    );

    fireEvent.click(screen.getByText('entrar'));

    await waitFor(() => expect(screen.getByTestId('auth')).toHaveTextContent('si'));
    expect(screen.getByTestId('id')).toHaveTextContent(PROFILE.id);
    expect(screen.getByTestId('email')).toHaveTextContent('cliente@iatsae.test');
    expect(screen.getByTestId('role')).toHaveTextContent('client');
    expect(screen.getByTestId('name')).toHaveTextContent('Cliente Real');
  });

  it('registra y deja la sesión iniciada (registro → login → /me)', async () => {
    mockBackend();
    render(
      <AuthProvider restoreSession={false}>
        <Harness />
      </AuthProvider>,
    );

    fireEvent.click(screen.getByText('registrar'));

    await waitFor(() => expect(screen.getByTestId('auth')).toHaveTextContent('si'));
    expect(screen.getByTestId('email')).toHaveTextContent('cliente@iatsae.test');
  });

  it('restaura la sesión al montar si la cookie de refresh es válida', async () => {
    mockBackend({ '/token/refresh': () => json({ access_token: 'tok', expires_in: 900 }, 200) });
    render(
      <AuthProvider>
        <Harness />
      </AuthProvider>,
    );

    await waitFor(() => expect(screen.getByTestId('auth')).toHaveTextContent('si'));
    expect(screen.getByTestId('id')).toHaveTextContent(PROFILE.id);
    expect(screen.getByTestId('role')).toHaveTextContent('client');
  });

  it('no inicia sesión al montar si no hay cookie de refresh válida', async () => {
    mockBackend(); // /token/refresh → 401
    render(
      <AuthProvider>
        <Harness />
      </AuthProvider>,
    );

    await waitFor(() => expect(screen.getByTestId('auth')).toHaveTextContent('no'));
  });
});
