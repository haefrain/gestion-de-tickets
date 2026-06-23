import { describe, it, expect } from 'vitest';
import { decodeJwtClaims } from './jwt';

// Emula un emisor real (Lexik): JSON -> bytes UTF-8 -> base64url.
function makeJwt(claims: Record<string, unknown>): string {
  const b64url = (obj: unknown): string => {
    const utf8 = String.fromCharCode(...new TextEncoder().encode(JSON.stringify(obj)));
    return btoa(utf8).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  };
  return `${b64url({ typ: 'JWT', alg: 'RS256' })}.${b64url(claims)}.firma`;
}

describe('decodeJwtClaims', () => {
  it('decodifica los claims del payload', () => {
    const token = makeJwt({
      username: '019ef235-6ffb-7e9c-ae73-f575fc1e1993',
      roles: ['ROLE_CLIENT'],
      exp: 99,
    });
    expect(decodeJwtClaims(token)).toEqual({
      username: '019ef235-6ffb-7e9c-ae73-f575fc1e1993',
      roles: ['ROLE_CLIENT'],
      exp: 99,
    });
  });

  it('decodifica claims con caracteres no ASCII (UTF-8)', () => {
    const token = makeJwt({ name: 'Álvaro Núñez' });
    expect(decodeJwtClaims(token)?.name).toBe('Álvaro Núñez');
  });

  it('devuelve null ante un token malformado', () => {
    expect(decodeJwtClaims('no-es-un-jwt')).toBeNull();
    expect(decodeJwtClaims('a.b.c')).toBeNull();
  });
});
