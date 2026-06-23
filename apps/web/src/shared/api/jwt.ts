// Decodifica los claims (payload) de un JWT SIN verificar la firma.
// El frontend no valida el token (eso es responsabilidad del backend); solo lee claims
// no sensibles (sub/username, roles) para poblar la sesión hasta que exista GET /api/v1/me.

export interface JwtClaims {
  /** Identificador del sujeto. Lexik emite `username` con el UserId (UUID v7). */
  sub?: string;
  username?: string;
  roles?: string[];
  exp?: number;
  iat?: number;
  [claim: string]: unknown;
}

/** Devuelve los claims del JWT, o `null` si el token está malformado. */
export function decodeJwtClaims(token: string): JwtClaims | null {
  const payload = token.split('.')[1];
  if (payload === undefined) {
    return null;
  }
  try {
    return JSON.parse(base64UrlDecode(payload)) as JwtClaims;
  } catch {
    return null;
  }
}

function base64UrlDecode(segment: string): string {
  const base64 = segment.replace(/-/g, '+').replace(/_/g, '/');
  const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=');
  const binary = atob(padded);
  // atob devuelve bytes latin1; reinterpretamos como UTF-8 por si un claim trae acentos.
  const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
  return new TextDecoder().decode(bytes);
}
