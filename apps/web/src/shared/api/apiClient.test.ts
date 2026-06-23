// Refresh-on-401 del apiClient (HU-L1-E1-03): ante un 401 renueva el access token con la cookie
// de refresh y reintenta la petición; si el refresh también falla, avisa para cerrar la sesión.
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { apiClient, setAccessToken, setOnUnauthorized } from './apiClient';

function json(body: unknown, status: number): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

describe('apiClient refresh-on-401', () => {
  beforeEach(() => {
    setAccessToken('expirado');
    setOnUnauthorized(null);
  });
  afterEach(() => vi.unstubAllGlobals());

  it('renueva el token y reintenta la petición tras un 401', async () => {
    let tickets = 0;
    vi.stubGlobal(
      'fetch',
      vi.fn((url: string | URL) => {
        const path = String(url);
        if (path.endsWith('/token/refresh')) {
          return Promise.resolve(json({ access_token: 'fresco', expires_in: 900 }, 200));
        }
        if (path.endsWith('/tickets')) {
          tickets += 1;
          return Promise.resolve(
            tickets === 1 ? json({ title: 'No autorizado' }, 401) : json({ data: [] }, 200),
          );
        }
        return Promise.resolve(json({}, 404));
      }),
    );

    const result = await apiClient.get<{ data: unknown[] }>('/tickets');

    expect(result.data).toEqual([]);
    expect(tickets).toBe(2); // 1ª da 401, 2ª (tras refresh) da 200
  });

  it('cierra la sesión (onUnauthorized) si el refresh también falla', async () => {
    const onUnauthorized = vi.fn();
    setOnUnauthorized(onUnauthorized);
    vi.stubGlobal(
      'fetch',
      vi.fn((url: string | URL) => {
        const path = String(url);
        if (path.endsWith('/token/refresh')) {
          return Promise.resolve(json({}, 401));
        }
        return Promise.resolve(json({ title: 'No autorizado' }, 401));
      }),
    );

    await expect(apiClient.get('/tickets')).rejects.toThrowError();
    expect(onUnauthorized).toHaveBeenCalledOnce();
  });
});
