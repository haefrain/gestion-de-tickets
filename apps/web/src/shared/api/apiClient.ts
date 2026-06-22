// Cliente HTTP único de la app (overview.md §8). Centraliza: base URL, cabecera
// Authorization: Bearer, credenciales para la cookie de refresh y normalización RFC 7807.
// Las vistas NO usan fetch suelto: consumen este cliente (vía TanStack Query en F6).

import { ApiError, type ProblemDetails } from './ProblemDetails';

const BASE_URL = import.meta.env.VITE_API_URL ?? '/api/v1';

// Access token en memoria (el refresh vive en cookie HttpOnly, no accesible por JS).
let accessToken: string | null = null;

export function setAccessToken(token: string | null): void {
  accessToken = token;
}

export function getAccessToken(): string | null {
  return accessToken;
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
  signal?: AbortSignal;
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
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
