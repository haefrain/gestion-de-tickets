# Seguridad — Autenticación y Autorización

**Prueba Técnica · IATSAE** · Fase **F4 · Contratos & Seguridad**

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.1 |
| **Fecha** | 2026-06-22 |
| **Mecanismo** | JWT (access + refresh) · RBAC |
| **Relacionados** | [`api/api-design.md`](api/api-design.md) · [`platform/redis.md`](platform/redis.md) |

## 1. Autenticación con JWT

Emisión vía `LexikJWTAuthenticationBundle` con firma **RS256** (par de claves pública/privada).

| Token | Vida | Contenido (claims) | Uso |
|---|---|---|---|
| **Access token** | ~15 min | `sub` (userId), `roles`, `exp`, `iat` | Autoriza cada petición |
| **Refresh token** | ~7 días | identificador opaco + `sub` | Renueva el access token |

**Flujo**

1. `POST /login` con credenciales → devuelve `access_token` + `refresh_token`.
2. El cliente envía `Authorization: Bearer <access_token>` en cada petición.
3. Al expirar el access, `POST /token/refresh` con el refresh → nuevo access (con **rotación** del refresh).
4. `POST /logout` **revoca** el refresh token (lista de revocación en Redis).

## 2. Almacenamiento del token en el cliente

- **Access token:** en memoria de la SPA (no `localStorage`) para reducir exposición a XSS.
- **Refresh token:** cookie `HttpOnly` + `Secure` + `SameSite=Strict`.
- **Trade-off:** la cookie httpOnly mitiga el robo por XSS; `SameSite=Strict` mitiga el CSRF del endpoint de refresh (token CSRF explícito: 2ª iteración, ver [ADR 0006](architecture/adr/0006-refresh-token-en-cookie-httponly.md)).

## 3. Autorización (RBAC)

Jerarquía de roles (cada uno hereda al anterior):

```
ROLE_CLIENT  ⊂  ROLE_AGENT  ⊂  ROLE_ADMIN
```

| Acción | Cliente | Agente | Admin |
|---|---|---|---|
| Registrarse / login | ✅ | ✅ | ✅ |
| Crear ticket | ✅ | ✅ | ✅ |
| Ver **sus** tickets | ✅ | ✅ | ✅ |
| Ver **todos** los tickets | ❌ | ✅ | ✅ |
| Cambiar estado / asignar | ❌ | ✅ | ✅ |
| Comentar | ✅ (en los suyos) | ✅ | ✅ |
| Gestionar usuarios y roles | ❌ | ❌ | ✅ |

La autorización se aplica en dos niveles: **rol** (acceso al endpoint) y **propiedad del recurso** (un cliente solo opera sus tickets).

## 4. Contraseñas

- Hashing con **Argon2id** (o bcrypt como alternativa), nunca en claro.
- Política mínima de longitud/complejidad validada en el registro.
- Mensajes de error que **no revelan** si un email existe.

## 5. Protecciones adicionales

| Amenaza | Mitigación |
|---|---|
| Fuerza bruta en login | Rate limiting por IP/usuario (contador en Redis) |
| XSS | Access token en memoria; cookies `HttpOnly`; escape en frontend |
| CSRF | `SameSite=Strict` en la cookie de refresh (token CSRF explícito: 2ª iteración, [ADR 0006](architecture/adr/0006-refresh-token-en-cookie-httponly.md)) |
| Inyección | Validación de entrada + consultas parametrizadas (Doctrine) |
| Exposición de cabeceras | Cabeceras de seguridad (HSTS, X-Content-Type-Options, etc.) |
| CORS abierto | Lista blanca con el origen del frontend |

## 6. Revocación de tokens

- La **rotación** (refresh) y el **logout** borran el refresh token en Redis (`GETDEL`/`DEL`): el anterior deja de existir y no se vuelve a aceptar. Una lista de revocación explícita para detectar reuso queda como 2ª iteración.
- El access token, al ser de vida corta, no se revoca individualmente (se confía en su expiración).

## 7. Pendiente de iterar

- Confirmar tiempos exactos de expiración (15 min / 7 días) según la demo.
- ~~Decidir si el refresh va en cookie httpOnly o en cuerpo~~ → decidido: cookie HttpOnly + `SameSite=Strict` ([ADR 0006](architecture/adr/0006-refresh-token-en-cookie-httponly.md)).
- Definir la política de contraseñas concreta.
