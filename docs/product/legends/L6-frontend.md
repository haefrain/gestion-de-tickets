# L6 · Experiencia de Usuario (Frontend)

**Backlog rico** · App **web** (React 19 + TS + MUI) · [↩ índice](../backlog.md)

> Frontend de apoyo que consume la API con JWT. MUI (tema claro/oscuro índigo), **TanStack Query** para datos de servidor, React Router para navegación, Storybook para componentes. Demuestra integración end-to-end.

---

## Épica L6-E1 · Autenticación en cliente

### HU-L6-E1-01 · Pantallas de login y registro

**Narrativa:** Como **usuario** quiero **iniciar sesión y registrarme desde la UI** para **usar el sistema**.

| Metadato | Valor |
|---|---|
| **Contexto** | Frontend · **Épica:** L6-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:frontend`, `type:story` |
| **Depende de** | HU-L1-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Login exitoso
  Dado credenciales válidas en el formulario
  Cuando envío el login
  Entonces se almacena el JWT y se redirige al panel

Escenario: Errores de la API
  Dado credenciales inválidas
  Entonces se muestran los errores (mapeados desde problem+json)
```

**UI / componentes:** vistas `Login` y `Register` con `TextField`, `Button` (MUI); validación de formulario y mensajes de error.

**Datos:** mutaciones de TanStack Query a `POST /login` y `POST /register`; `apiClient` centraliza llamadas y errores.

**Estado/rutas:** al autenticar, guarda el access token en memoria y refresh en cookie httpOnly.

**Accesibilidad:** labels asociadas, foco inicial, errores con `aria-live`.

**Tests** · *Unit (Vitest+RTL):* validación y render de errores. *E2E (Playwright):* login y redirección.

---

### HU-L6-E1-02 · Sesión y rutas protegidas

**Narrativa:** Como **usuario** quiero **que la app gestione mi sesión** para **navegar de forma segura**.

| Metadato | Valor |
|---|---|
| **Contexto** | Frontend · **Épica:** L6-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:frontend`, `type:story` |
| **Depende de** | HU-L6-E1-01, HU-L1-E1-03 |

**Criterios de aceptación**

```gherkin
Escenario: Ruta protegida sin sesión
  Dado que no tengo sesión
  Cuando accedo a una ruta privada
  Entonces se me redirige a login

Escenario: Refresh transparente
  Dado que mi access token expira
  Cuando hago una petición
  Entonces el token se refresca y la petición continúa
```

**UI / componentes:** `AuthProvider` (Context) y `RequireAuth` (guard por rol); `AppLayout` con AppBar y navegación.

**Datos:** interceptor de `apiClient` adjunta el JWT y refresca ante 401; estado de sesión en Context.

**Accesibilidad:** navegación por teclado en el layout; indicación de estado de sesión.

**Tests** · *Unit:* `RequireAuth` redirige sin sesión. *E2E:* refresh transparente en una acción.

---

## Épica L6-E2 · Gestión de tickets en UI

### HU-L6-E2-01 · Listado con búsqueda y filtros

**Narrativa:** Como **usuario** quiero **ver y filtrar tickets** para **encontrar lo que necesito**.

| Metadato | Valor |
|---|---|
| **Contexto** | Frontend · **Épica:** L6-E2 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:frontend`, `type:story` |
| **Depende de** | HU-L2-E1-03, HU-L3-E2-02 |

**Criterios de aceptación**

```gherkin
Escenario: Listado con filtros
  Dado que estoy autenticado
  Cuando abro el listado y aplico búsqueda/filtros
  Entonces veo los tickets de mi alcance, paginados

Escenario: Estados de datos
  Cuando la carga está en curso, falla o no hay resultados
  Entonces se muestran los estados loading / error / empty
```

**UI / componentes:** `TicketTable` (MUI Table), `SearchBar`, `StatusChip`/`PriorityChip`, `CursorPagination`.

**Datos:** `useTickets` (TanStack Query) consume `GET /tickets` y `GET /search/tickets`; cache y revalidación automáticas.

**Accesibilidad:** tabla con cabeceras semánticas; controles de filtro etiquetados.

**Tests** · *Unit:* render de estados loading/error/empty. *E2E:* buscar y filtrar.

---

### HU-L6-E2-02 · Crear, ver y actualizar ticket

**Narrativa:** Como **usuario** quiero **crear y gestionar tickets desde la UI** para **operar end-to-end**.

| Metadato | Valor |
|---|---|
| **Contexto** | Frontend · **Épica:** L6-E2 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:frontend`, `type:story` |
| **Depende de** | HU-L2-E1-01, HU-L2-E1-05 |

**Criterios de aceptación**

```gherkin
Escenario: Crear ticket
  Cuando completo el formulario de creación
  Entonces el ticket se crea y aparece en mi listado

Escenario: Gestionar desde el detalle
  Dado que soy Agente en el detalle de un ticket
  Cuando cambio el estado o lo asigno
  Entonces la vista refleja el cambio (y comentarios e historial)
```

**UI / componentes:** `TicketForm` (crear/editar), vista `TicketDetail` con `CommentList`, acciones de transición/asignación (según rol).

**Datos:** mutaciones de TanStack Query a `POST /tickets`, `POST /tickets/{id}/transitions`, `POST /tickets/{id}/comments`; invalidación de queries tras mutar.

**Accesibilidad:** formularios accesibles; confirmaciones en `Dialog`; feedback en `Snackbar`.

**Tests** · *Unit:* validación del formulario. *E2E:* crear → ver → cambiar estado.
