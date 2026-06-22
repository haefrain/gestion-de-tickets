# L1 · Identidad y Acceso

**Backlog rico** · Bounded context **Identity** · [↩ índice](../backlog.md)

> Autenticación con JWT (RS256) y autorización por roles (Cliente, Agente, Admin). Base de seguridad de toda la API. Entidad raíz `User`; VOs `UserId` (UUID v7), `Email`, `Role`.

---

## Épica L1-E1 · Autenticación JWT

### HU-L1-E1-01 · Registro de cliente

**Narrativa:** Como **visitante** quiero **registrarme** para **poder crear tickets**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | — |

**Criterios de aceptación**

```gherkin
Escenario: Registro exitoso
  Dado un email no registrado y una contraseña válida
  Cuando hago POST /api/v1/register
  Entonces se crea el usuario con rol ROLE_CLIENT
  Y recibo 201 con su id y email

Escenario: Email duplicado
  Dado un email ya registrado
  Cuando hago POST /api/v1/register
  Entonces recibo 409 sin revelar datos del usuario

Escenario: Datos inválidos
  Dado un email con formato inválido o contraseña débil
  Cuando hago POST /api/v1/register
  Entonces recibo 422 (application/problem+json)
```

**Contrato API** · `POST /api/v1/register` (público)
Request `{ "email", "password", "name"? }` → `201 { "id", "email", "roles" }` · errores `409`, `422`.

**Diseño técnico**
- **Comando:** `RegisterUserCommand(email, password, name)`
- **Handler:** `RegisterUserHandler` → `User::register(...)` → `UserRepository::save()`
- **Dominio:** agregado `User`; VO `Email` (valida formato); asigna `ROLE_CLIENT`; **invariante:** email único (verificado vía puerto)
- **Puerto:** `UserRepository::save()`, `PasswordHasher::hash()`

**Infraestructura**
- **PostgreSQL:** tabla `users`. **Hashing:** adaptador `PasswordHasher` (Argon2id).

**Seguridad:** endpoint público; contraseña hasheada; el error de duplicado no revela existencia.

**Tests**
- *Unit:* `Email` inválido lanza excepción; `User::register` asigna `ROLE_CLIENT`.
- *Integración:* crea en BD; `409` ante duplicado.
- *E2E:* registro desde la UI.

---

### HU-L1-E1-02 · Login con emisión de JWT

**Narrativa:** Como **usuario registrado** quiero **autenticarme** para **obtener un token de acceso**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | HU-L1-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Login exitoso
  Dado credenciales válidas
  Cuando hago POST /api/v1/login
  Entonces recibo access_token (JWT RS256) y refresh_token

Escenario: Credenciales inválidas
  Dado un email o contraseña incorrectos
  Cuando hago POST /api/v1/login
  Entonces recibo 401 sin distinguir si el email existe
```

**Contrato API** · `POST /api/v1/login` (público)
Request `{ "email", "password" }` → `200 { "access_token", "refresh_token", "expires_in" }` · error `401`.

**Diseño técnico**
- Autenticación vía `LexikJWTAuthenticationBundle`; access token con claims `sub`, `roles`, `exp` (~15 min); refresh token opaco (~7 días).
- **Puerto:** `RefreshTokenStore` (persistencia/rotación del refresh).

**Infraestructura**
- **PostgreSQL/Redis:** almacén de refresh tokens. **Claves:** firma RS256 (par pública/privada).

**Seguridad:** respuesta uniforme ante fallo; access de vida corta; ver [`security.md`](../../security.md).

**Tests**
- *Unit:* generación de claims correcta (doble del emisor).
- *Integración:* login devuelve ambos tokens; `401` con credenciales malas.
- *E2E:* login desde la UI y acceso a ruta protegida.

---

### HU-L1-E1-03 · Refresh de token

**Narrativa:** Como **usuario autenticado** quiero **renovar mi token** para **no re-loguearme constantemente**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E1 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | HU-L1-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Refresh válido
  Dado un refresh_token válido
  Cuando hago POST /api/v1/token/refresh
  Entonces recibo un nuevo access_token
  Y el refresh_token se rota (el anterior queda revocado)

Escenario: Refresh inválido o expirado
  Dado un refresh_token revocado o expirado
  Cuando hago POST /api/v1/token/refresh
  Entonces recibo 401
```

**Contrato API** · `POST /api/v1/token/refresh`
Request `{ "refresh_token" }` (o cookie httpOnly) → `200 { "access_token", "refresh_token" }` · error `401`.

**Diseño técnico:** valida el refresh contra `RefreshTokenStore`, emite nuevo access y **rota** el refresh.

**Infraestructura:** **Redis** para revocación (TTL = expiración del token).

**Seguridad:** rotación de refresh; el revocado no se acepta. Protección CSRF si va en cookie.

**Tests**
- *Unit:* la rotación invalida el token previo.
- *Integración:* refresh válido renueva; revocado da `401`.

---

### HU-L1-E1-04 · Logout / invalidación

**Narrativa:** Como **usuario autenticado** quiero **cerrar sesión** para **invalidar mi token**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E1 |
| **Prioridad** | Should · **Estimación:** 2 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | HU-L1-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Logout
  Dado que estoy autenticado
  Cuando hago POST /api/v1/logout
  Entonces mi refresh_token queda revocado
  Y deja de poder renovar access tokens
```

**Contrato API** · `POST /api/v1/logout` (autenticado) → `204`.

**Diseño técnico:** añade el refresh a la lista de revocación; el access expira por sí solo.

**Infraestructura:** **Redis** (lista de revocación con TTL).

**Tests:** *Integración:* tras logout, el refresh da `401`.

---

## Épica L1-E2 · Autorización por roles (RBAC)

### HU-L1-E2-01 · Modelo de roles y permisos

**Narrativa:** Como **equipo** quiero **definir los roles Cliente/Agente/Admin** para **controlar el acceso**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E2 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | — |

**Criterios de aceptación**

```gherkin
Escenario: Jerarquía de roles
  Dado el modelo de roles
  Entonces ROLE_CLIENT ⊂ ROLE_AGENT ⊂ ROLE_ADMIN
  Y cada endpoint declara su rol mínimo
```

**Diseño técnico:** VO `Role`; jerarquía configurada en seguridad de Symfony; `User` porta su colección de roles.

**Seguridad:** matriz de permisos definida en [`security.md`](../../security.md).

**Tests:** *Unit:* la jerarquía concede permisos heredados.

---

### HU-L1-E2-02 · Restricción de endpoints por rol

**Narrativa:** Como **equipo** quiero **que la API rechace accesos no autorizados** para **proteger los datos**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E2 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | HU-L1-E2-01 |

**Criterios de aceptación**

```gherkin
Escenario: Acceso denegado por rol
  Dado que soy Cliente
  Cuando accedo a un recurso de gestión de Agente/Admin
  Entonces recibo 403

Escenario: Alcance por propiedad
  Dado que soy Cliente
  Entonces solo veo y edito mis propios tickets
```

**Diseño técnico:** *voters* de Symfony para autorización por rol y por propiedad del recurso (requester == usuario actual).

**Seguridad:** doble control (rol + propiedad).

**Tests:** *Integración:* `403` para Cliente en endpoints de Agente; un Cliente no accede a tickets ajenos.

---

### HU-L1-E2-03 · Gestión de usuarios por Admin

**Narrativa:** Como **Admin** quiero **gestionar usuarios y roles** para **administrar el sistema**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E2 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | HU-L1-E2-02 |

**Criterios de aceptación**

```gherkin
Escenario: Cambiar rol
  Dado que soy Admin
  Cuando hago PATCH /api/v1/users/{id} con un nuevo rol
  Entonces el rol del usuario se actualiza

Escenario: Desactivar cuenta
  Dado que soy Admin
  Cuando desactivo una cuenta
  Entonces ese usuario no puede autenticarse
```

**Contrato API** · `GET /api/v1/users` (cursor) · `PATCH /api/v1/users/{id}` — solo `ROLE_ADMIN`.

**Diseño técnico:** `ChangeUserRoleCommand`, `DeactivateUserCommand` con sus handlers.

**Infraestructura:** **PostgreSQL** (`users`); **Redis:** invalida cache de usuario.

**Tests:** *Integración:* solo Admin; el desactivado no puede loguear.

---

## Épica L1-E3 · Perfil de usuario

### HU-L1-E3-01 · Ver y editar perfil

**Narrativa:** Como **usuario autenticado** quiero **ver y editar mi perfil** para **mantener mis datos**.

| Metadato | Valor |
|---|---|
| **Contexto** | Identity · **Épica:** L1-E3 |
| **Prioridad** | Could · **Estimación:** 2 · **Labels:** `area:auth`, `type:story` |
| **Depende de** | HU-L1-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Ver perfil
  Dado que estoy autenticado
  Cuando hago GET /api/v1/me
  Entonces recibo mis datos (id, email, name, roles)

Escenario: Editar perfil
  Cuando hago PATCH /api/v1/me con datos válidos
  Entonces se actualizan nombre y/o contraseña
```

**Contrato API** · `GET /api/v1/me` · `PATCH /api/v1/me` (autenticado).

**Diseño técnico:** query `GetMyProfile`; comando `UpdateProfileCommand` (revalida contraseña con `PasswordHasher`).

**Infraestructura:** **Redis** (`user:{id}`, TTL 10 min, invalida al editar).

**Tests:** *Integración:* `GET /me` devuelve el usuario; `PATCH` actualiza y re-hashea la contraseña.
