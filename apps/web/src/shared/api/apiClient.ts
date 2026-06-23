// Cliente HTTP único de la app (overview.md §8). Centraliza: base URL, cabecera
// Authorization: Bearer, credenciales para la cookie de refresh y normalización RFC 7807.
// Incluye refresh-on-401 transparente: ante un 401 renueva el access token con la cookie de
// refresh (HU-L1-E1-03) y reintenta la petición una vez; si el refresh falla, avisa para
// cerrar la sesión. Las vistas NO usan fetch suelto: consumen este cliente (vía TanStack Query).

import { ApiError, type ProblemDetails } from './ProblemDetails';

const BASE_URL = import.meta.env.VITE_API_URL ?? '/api/v1';

// Access token en memoria (el refresh vive en cookie HttpOnly, no accesible por JS).
let accessToken: string | null = null;

// Refresh en curso compartido: varias peticiones que reciben 401 a la vez disparan UN solo
// refresh y esperan el mismo resultado.
let refreshInFlight: Promise<boolean> | null = null;

// Se invoca cuando el refresh falla (sesión expirada/ausente): el AuthProvider lo usa para
// limpiar la sesión y enviar al login.
let onUnauthorized: (() => void) | null = null;

export function setAccessToken(token: string | null): void {
  accessToken = token;
}

export function getAccessToken(): string | null {
  return accessToken;
}

export function setOnUnauthorized(handler: (() => void) | null): void {
  onUnauthorized = handler;
}

async function performRefresh(): Promise<boolean> {
  try {
    const response = await fetch(`${BASE_URL}/token/refresh`, {
      method: 'POST',
      credentials: 'include',
    });
    if (!response.ok) {
      return false;
    }
    const data = (await response.json()) as { access_token?: unknown };
    if (typeof data.access_token !== 'string') {
      return false;
    }
    accessToken = data.access_token;
    return true;
  } catch {
    return false;
  }
}

/**
 * Renueva el access token con la cookie de refresh. Comparte el intento en curso para no lanzar
 * refresh en paralelo. Devuelve true si quedó un nuevo access token en memoria.
 */
export function refreshSession(): Promise<boolean> {
  refreshInFlight ??= performRefresh().finally(() => {
    refreshInFlight = null;
  });
  return refreshInFlight;
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
  signal?: AbortSignal;
}

async function request<T>(path: string, options: RequestOptions = {}, retried = false): Promise<T> {
  const headers: Record<string, string> = { 'Content-Type': 'application/json' };
  if (accessToken !== null) {
    headers.Authorization = `Bearer ${accessToken}`;
  }

  const response = await fetch(`${BASE_URL}${path}`, {
    method: options.method ?? 'GET',
    headers,
    credentials: 'include',
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
    signal: options.signal,
  });

  // 401: un refresh transparente (una sola vez) y reintento. El login y el propio refresh quedan
  // fuera: un 401 ahí es definitivo (credenciales inválidas / sin sesión recuperable).
  if (response.status === 401 && !retried && path !== '/login' && path !== '/token/refresh') {
    if (await refreshSession()) {
      return request<T>(path, options, true);
    }
    onUnauthorized?.();
  }

  if (!response.ok) {
    throw new ApiError(response.status, await parseProblem(response));
  }

  if (response.status === 204) {
    return undefined as T;
  }
  return (await response.json()) as T;
}

async function parseProblem(response: Response): Promise<ProblemDetails> {
  try {
    const data = (await response.json()) as Partial<ProblemDetails>;
    return {
      type: data.type ?? 'about:blank',
      title: data.title ?? response.statusText,
      status: data.status ?? response.status,
      detail: data.detail,
      instance: data.instance,
      errors: data.errors,
    };
  } catch {
    return { type: 'about:blank', title: response.statusText, status: response.status };
  }
}

export const apiClient = {
  get: <T>(path: string, signal?: AbortSignal): Promise<T> => request<T>(path, { signal }),
  post: <T>(path: string, body?: unknown): Promise<T> => request<T>(path, { method: 'POST', body }),
  patch: <T>(path: string, body?: unknown): Promise<T> => request<T>(path, { method: 'PATCH', body }),
  del: <T>(path: string): Promise<T> => request<T>(path, { method: 'DELETE' }),
};
