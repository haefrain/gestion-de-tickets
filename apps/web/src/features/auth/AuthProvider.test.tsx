// Regresión del bug "pantalla en blanco tras registro": el login (HU-L1-E1-02) devuelve
// { access_token, refresh_token, expires_in } SIN user; el provider debe poblar la sesión
// derivándola del JWT + los datos del formulario (perfil real llegará de /me, HU-L1-E3-01).
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { AuthProvider } from './AuthProvider';
import { useAuth } from './auth-context';

/** JWT de prueba: header y firma irrelevantes (el frontend no verifica la firma). */
function makeJwt(claims: Record<string, unknown>): string {
  const b64url = (obj: unknown): string =>
    btoa(JSON.stringify(obj)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  return `${b64url({ typ: 'JWT', alg: 'RS256' })}.${b64url(claims)}.firma`;
}

const TOKEN = makeJwt({
  username: '019ef235-6ffb-7e9c-ae73-f575fc1e1993',
  roles: ['ROLE_CLIENT'],
  iat: 1,
  exp: 2,
});

function Harness() {
  const { user, isAuthenticated, register, login } = useAuth();
  return (
    <div>
      <button
        onClick={() =>
          void register({ name: 'Cliente Demo', email: 'cliente@iatsae.test', password: 'Sup3rS3cret!' })
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

describe('AuthProvider contra el contrato real del backend', () => {
  beforeEach(() => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: string | URL) => {
        const path = String(url);
        if (path.endsWith('/register')) {
          return new Response(
            JSON.stringify({ id: TOKEN, email: 'cliente@iatsae.test', roles: ['ROLE_CLIENT'] }),
            { status: 201, headers: { 'Content-Type': 'application/json' } },
          );
        }
        if (path.endsWith('/login')) {
          return new Response(
            JSON.stringify({ access_token: TOKEN, refresh_token: 'opaco', expires_in: 900 }),
            { status: 200, headers: { 'Content-Type': 'application/json' } },
          );
        }
        return new Response('{}', { status: 404, headers: { 'Content-Type': 'application/json' } });
      }),
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('puebla la sesión tras un registro exitoso (no deja user en undefined)', async () => {
    render(
      <AuthProvider>
        <Harness />
      </AuthProvider>,
    );

    fireEvent.click(screen.getByText('registrar'));

    await waitFor(() => expect(screen.getByTestId('id')).not.toHaveTextContent(''));
    expect(screen.getByTestId('auth')).toHaveTextContent('si');
    expect(screen.getByTestId('id')).toHaveTextContent('019ef235-6ffb-7e9c-ae73-f575fc1e1993');
    expect(screen.getByTestId('email')).toHaveTextContent('cliente@iatsae.test');
    expect(screen.getByTestId('role')).toHaveTextContent('client');
    // El name del formulario se conserva en el registro.
    expect(screen.getByTestId('name')).toHaveTextContent('Cliente Demo');
  });

  it('puebla la sesión tras un login directo, derivando el nombre del email', async () => {
    render(
      <AuthProvider>
        <Harness />
      </AuthProvider>,
    );

    fireEvent.click(screen.getByText('entrar'));

    await waitFor(() => expect(screen.getByTestId('id')).not.toHaveTextContent(''));
    expect(screen.getByTestId('email')).toHaveTextContent('cliente@iatsae.test');
    expect(screen.getByTestId('role')).toHaveTextContent('client');
    // Sin name en el login directo: se deriva de la parte local del email (hasta que exista /me).
    expect(screen.getByTestId('name')).toHaveTextContent('Cliente');
  });
});
