# ADR 0006 · Refresh token en cookie HttpOnly con SameSite=Strict (sin token CSRF)

- **Estado:** Aceptada · 2026-06-22

## Contexto

El login (HU-L1-E1-02) emite un **access token** (JWT RS256, de vida corta, viaja en `Authorization: Bearer` y se guarda en memoria de la SPA) y un **refresh token** opaco (vida larga, en Redis). Al implementar la renovación (HU-L1-E1-03) hay que decidir **dónde almacena el cliente el refresh** y **cómo se protege el endpoint** `POST /api/v1/token/refresh`.

Dos fuerzas en tensión:

- **XSS:** un refresh accesible por JavaScript (p. ej. en el body de la respuesta o en `localStorage`) queda expuesto si hay XSS.
- **CSRF:** sólo afecta a credenciales que el navegador adjunta **automáticamente** (cookies). Las rutas que se autentican con `Authorization: Bearer` son inmunes por diseño. El único vector CSRF es, precisamente, un endpoint que se autentique con una cookie auto-enviada.

El firewall `main` es **stateless** (sin sesión de servidor), lo que condiciona las opciones de CSRF disponibles.

## Decisión

El refresh token viaja en una **cookie HttpOnly + `SameSite=Strict` + `Secure`** (este último condicional por entorno: desactivado en desarrollo sobre HTTP), con `path` acotado a los endpoints de token. La cookie **no es legible por JavaScript** y **no se devuelve en el body**.

- El **login** pasa a setear esa cookie; su body deja de incluir `refresh_token` (queda `{ access_token, expires_in }`).
- `POST /api/v1/token/refresh` lee la cookie, **rota** el refresh (lo consume/revoca y emite uno nuevo) y re-emite el access.
- **No se añade token CSRF.** `SameSite=Strict` es la mitigación: el navegador no envía la cookie en peticiones iniciadas desde otro sitio. Además, la Same-Origin Policy impide a un atacante leer la respuesta, por lo que un CSRF exitoso no permitiría robar el token.

Alcance: backend. El cableado del refresh automático en la SPA se difiere a la capa frontend (L6).

## Consecuencias

- (+) El refresh no es accesible por JS: mitiga su robo ante XSS (el objetivo principal).
- (+) `SameSite=Strict` cubre el vector CSRF del endpoint sin la complejidad del *double-submit token*.
- (+) **Rotación en cada uso:** un refresh filtrado caduca en cuanto se usa una vez; facilita detectar reuso a futuro.
- (+) Una **fábrica de cookie única** (Login/Refresh/Logout) centraliza los atributos de seguridad.
- (−) **Cambia el contrato de la HU-L1-E1-02 ya entregada**: el body del login pierde `refresh_token`; obliga a actualizar `docs/` (api-design, security, fichas) y cualquier cliente.
- (−) Menor defensa en profundidad que `SameSite=Strict` + token CSRF; se acepta conscientemente y se reabre si se despliega la SPA en un origen distinto al de la API.
- (−) `Secure` condicional por entorno añade un parámetro de configuración (`app.refresh_cookie_secure`).

## Alternativas consideradas

- **Refresh en el body JSON** (como en la primera versión de E1-02): simple, pero accesible por JS y por tanto expuesto a XSS. Descartada.
- **Cookie + token CSRF *double-submit*** (modelo que documentaba `security.md`): defensa en profundidad, pero añade cookie CSRF, header `X-CSRF-Token` y su validación por un riesgo ya cubierto por `SameSite=Strict`. Pospuesta a 2ª iteración.
- **CSRF nativo de Symfony (synchronizer token, stateful):** exige sesión de servidor, lo que choca con el firewall `stateless` y el diseño JWT. Descartada.
