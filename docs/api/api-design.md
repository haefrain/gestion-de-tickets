# Diseño de la API — Sistema de Gestión de Tickets

**Prueba Técnica · IATSAE** · Fase **F4 · Contratos & Seguridad**

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.1 |
| **Fecha** | 2026-06-22 |
| **Estilo** | REST · JSON · `/api/v1` · stateless (JWT) |
| **Relacionados** | [`security.md`](../security.md) · [`architecture/overview.md`](../architecture/overview.md) |

## 1. Principios

- **REST** sobre recursos en plural; **JSON** en request y response.
- **Stateless**: cada petición lleva su `Authorization: Bearer <JWT>`.
- **Versionado por URL**: prefijo `/api/v1`.
- **Errores estándar**: RFC 7807 (`application/problem+json`).
- **Paginación por cursor** para listados.

## 2. Convenciones

| Aspecto | Convención |
|---|---|
| Métodos | `GET` (leer) · `POST` (crear/acción) · `PATCH` (modificar) · `DELETE` (borrar) |
| Nombres | recursos en plural y kebab/snake según campo (`/tickets`, `created_at`) |
| Códigos | `200` ok · `201` creado · `204` sin contenido · `400/422` validación · `401` no autenticado · `403` sin permiso · `404` no existe · `409` conflicto de estado |
| Fechas | ISO 8601 UTC (`2026-06-22T10:00:00Z`) |
| IDs | UUID v7 en el cuerpo y la URL |

## 3. Autenticación

Todas las rutas salvo `register`, `login` y `token/refresh` requieren cabecera:

```
Authorization: Bearer <access_token>
```

Detalle del flujo JWT y RBAC en [`security.md`](../security.md).

## 4. Formato de errores (RFC 7807)

`Content-Type: application/problem+json`

```json
{
  "type": "https://docs.iatsae.dev/errors/validation",
  "title": "La validación ha fallado",
  "status": 422,
  "detail": "El campo 'title' es obligatorio",
  "instance": "/api/v1/tickets",
  "errors": [
    { "field": "title", "message": "No puede estar vacío" }
  ]
}
```

Cada tipo de error tiene un `type` estable y documentado; `errors[]` es una extensión para validación de campos.

## 5. Paginación por cursor

**Request:** `GET /api/v1/tickets?limit=20&cursor=eyJpZCI6...`

**Response:**

```json
{
  "data": [ { "id": "...", "title": "..." } ],
  "page": {
    "limit": 20,
    "next_cursor": "eyJpZCI6...",
    "has_more": true
  }
}
```

El cursor es opaco (codifica el último ID/orden). Cuando `has_more` es `false`, `next_cursor` es `null`.

## 6. Recursos y endpoints (MVP)

### Identidad

| Método | Ruta | Descripción | Rol |
|---|---|---|---|
| `POST` | `/api/v1/register` | Registro de cliente | Público |
| `POST` | `/api/v1/login` | Login → access en body + refresh en cookie HttpOnly | Público |
| `POST` | `/api/v1/token/refresh` | Renueva el access leyendo la cookie de refresh (rota la cookie) | Público (con cookie) |
| `POST` | `/api/v1/logout` | Revocar refresh token | Autenticado |
| `GET` | `/api/v1/me` | Perfil actual | Autenticado |

### Tickets

| Método | Ruta | Descripción | Rol |
|---|---|---|---|
| `POST` | `/api/v1/tickets` | Crear ticket | Cliente |
| `GET` | `/api/v1/tickets` | Listar (cursor + filtros) | Autenticado (alcance por rol) |
| `GET` | `/api/v1/tickets/{id}` | Detalle | Dueño / Agente / Admin |
| `PATCH` | `/api/v1/tickets/{id}` | Editar título/descripción | Dueño / Agente |
| `POST` | `/api/v1/tickets/{id}/transitions` | Cambiar estado | Agente |
| `POST` | `/api/v1/tickets/{id}/assignment` | Asignar agente | Agente / Admin |
| `POST` | `/api/v1/tickets/{id}/comments` | Comentar | Autorizado |
| `GET` | `/api/v1/tickets/{id}/comments` | Listar comentarios | Autorizado |

### Búsqueda

| Método | Ruta | Descripción | Rol |
|---|---|---|---|
| `GET` | `/api/v1/search/tickets?q=&status=&priority=&assignee=&cursor=` | Búsqueda + filtros (Elasticsearch) | Autenticado (alcance por rol) |

### Administración

| Método | Ruta | Descripción | Rol |
|---|---|---|---|
| `GET` | `/api/v1/users` | Listar usuarios | Admin |
| `PATCH` | `/api/v1/users/{id}` | Cambiar rol / activar | Admin |

## 7. Ejemplo — Crear ticket

**Request**

```
POST /api/v1/tickets
Authorization: Bearer <access_token>
Content-Type: application/json

{ "title": "No puedo iniciar sesión", "description": "Error 500 al entrar", "priority": "high", "category": "auth" }
```

**Response `201`**

```json
{
  "id": "0190f3c2-...-7a1b",
  "title": "No puedo iniciar sesión",
  "status": "open",
  "priority": "high",
  "category": "auth",
  "requester_id": "0190...",
  "assignee_id": null,
  "created_at": "2026-06-22T10:00:00Z"
}
```

## 8. Transversal

- **Idempotencia:** las acciones de creación admitirán cabecera `Idempotency-Key` (futuro) para evitar duplicados.
- **Rate limiting:** aplicado a `login` y endpoints sensibles (ver `security.md`).
- **CORS:** restringido al origen del frontend.

## 9. Pendiente de iterar

- Generar `openapi.yaml` formal a partir de esta guía si se decide.
- Confirmar campos de filtro exactos de búsqueda con F5 (Elasticsearch).
- Definir el contrato de adjuntos (versión sencilla).
