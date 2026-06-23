// Construcción de la sesión del frontend a partir del JWT que emite el login (HU-L1-E1-02).
// El login devuelve solo tokens (sin perfil); el perfil real llegará de GET /api/v1/me
// (HU-L1-E3-01). Hasta entonces derivamos el User del JWT + lo que el usuario ya tecleó.

import { decodeJwtClaims } from '../../shared/api/jwt';
import { ROLES, type Role, type User } from '../../shared/api/types';

const ROLE_PREFIX = 'ROLE_';

/** Mapea los roles del token (`ROLE_CLIENT`, …) al Role del frontend (`client`, …). */
export function roleFromTokenRoles(roles: readonly string[] | undefined): Role {
  for (const raw of roles ?? []) {
    const candidate = (raw.startsWith(ROLE_PREFIX) ? raw.slice(ROLE_PREFIX.length) : raw).toLowerCase();
    if ((ROLES as readonly string[]).includes(candidate)) {
      return candidate as Role;
    }
  }
  return 'client';
}

/** Nombre legible de respaldo a partir del email (hasta que /me devuelva el real). */
export function displayNameFromEmail(email: string): string {
  const local = email.split('@')[0] ?? email;
  const words = local.split(/[._-]+/).filter(Boolean);
  if (words.length === 0) {
    return email;
  }
  return words.map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

export interface SessionInput {
  accessToken: string;
  email: string;
  /** Nombre tecleado en el registro; ausente en el login directo. */
  name?: string;
}

/** Deriva el User de la sesión del JWT + los datos del formulario. */
export function sessionUserFromToken({ accessToken, email, name }: SessionInput): User {
  const claims = decodeJwtClaims(accessToken);
  return {
    id: claims?.sub ?? claims?.username ?? email,
    name: name?.trim() ? name.trim() : displayNameFromEmail(email),
    email,
    role: roleFromTokenRoles(claims?.roles),
  };
}
