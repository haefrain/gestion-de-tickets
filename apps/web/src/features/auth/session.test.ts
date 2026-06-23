import { describe, it, expect } from 'vitest';
import { displayNameFromEmail, roleFromTokenRoles, sessionUserFromToken } from './session';

function makeJwt(claims: Record<string, unknown>): string {
  const b64url = (obj: unknown): string =>
    btoa(JSON.stringify(obj)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  return `${b64url({ typ: 'JWT', alg: 'RS256' })}.${b64url(claims)}.firma`;
}

describe('roleFromTokenRoles', () => {
  it('mapea los roles del backend al Role del frontend', () => {
    expect(roleFromTokenRoles(['ROLE_CLIENT'])).toBe('client');
    expect(roleFromTokenRoles(['ROLE_AGENT'])).toBe('agent');
    expect(roleFromTokenRoles(['ROLE_ADMIN'])).toBe('admin');
  });

  it('cae a client ante roles ausentes o desconocidos', () => {
    expect(roleFromTokenRoles(undefined)).toBe('client');
    expect(roleFromTokenRoles([])).toBe('client');
    expect(roleFromTokenRoles(['ROLE_DESCONOCIDO'])).toBe('client');
  });

  it('elige el primer rol conocido cuando hay varios', () => {
    expect(roleFromTokenRoles(['ROLE_DESCONOCIDO', 'ROLE_ADMIN'])).toBe('admin');
  });
});

describe('displayNameFromEmail', () => {
  it('deriva un nombre legible de la parte local', () => {
    expect(displayNameFromEmail('ana.perez@iatsae.test')).toBe('Ana Perez');
    expect(displayNameFromEmail('soporte@iatsae.test')).toBe('Soporte');
  });
});

describe('sessionUserFromToken', () => {
  it('usa el id y el rol del JWT y el name del formulario cuando está', () => {
    const token = makeJwt({ username: '019ef235-6ffb-7e9c-ae73-f575fc1e1993', roles: ['ROLE_AGENT'] });
    expect(sessionUserFromToken({ accessToken: token, email: 'a@iatsae.test', name: 'Ana Pérez' })).toEqual({
      id: '019ef235-6ffb-7e9c-ae73-f575fc1e1993',
      name: 'Ana Pérez',
      email: 'a@iatsae.test',
      role: 'agent',
    });
  });

  it('deriva el name del email cuando no se provee', () => {
    const token = makeJwt({ username: 'abc', roles: ['ROLE_CLIENT'] });
    const user = sessionUserFromToken({ accessToken: token, email: 'juan.lopez@iatsae.test' });
    expect(user.name).toBe('Juan Lopez');
    expect(user.role).toBe('client');
  });

  it('cae al email como id si el token está malformado', () => {
    const user = sessionUserFromToken({ accessToken: 'roto', email: 'x@iatsae.test' });
    expect(user.id).toBe('x@iatsae.test');
    expect(user.role).toBe('client');
  });
});
