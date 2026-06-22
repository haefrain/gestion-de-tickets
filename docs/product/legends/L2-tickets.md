# L2 · Gestión de Tickets

**Backlog rico** · Bounded context **Ticketing** (núcleo) · [↩ índice](../backlog.md)

> Núcleo del dominio modelado con arquitectura hexagonal sobre PostgreSQL. Agregado raíz `Ticket`; VOs `TicketId` (UUID v7), `TicketStatus`, `Priority`, `Category`; entidad hija `Comment`; eventos `TicketCreated`, `TicketStatusChanged`, `TicketAssigned`, `TicketCommented`.

---

## Épica L2-E1 · Ciclo de vida del ticket

### HU-L2-E1-01 · Crear ticket

**Narrativa:** Como **Cliente** quiero **crear un ticket** para **reportar una incidencia**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L1-E1-02 |

**Criterios de aceptación**

```gherkin
Escenario: Creación exitosa
  Dado que estoy autenticado como Cliente
  Y envío un título y una descripción válidos
  Cuando hago POST /api/v1/tickets
  Entonces el ticket se crea en estado "open" asociado a mí
  Y recibo 201 con el recurso creado
  Y se emite el evento TicketCreated

Escenario: Datos inválidos
  Dado que el título está vacío
  Cuando hago POST /api/v1/tickets
  Entonces recibo 422 (application/problem+json) y el ticket no se crea

Escenario: No autenticado
  Dado que no envío JWT válido
  Cuando hago POST /api/v1/tickets
  Entonces recibo 401
```

**Contrato API** · `POST /api/v1/tickets` (rol `ROLE_CLIENT`)
Request `{ "title", "description", "priority"?, "category"? }` → `201 { id, title, status:"open", priority, category, requester_id, assignee_id:null, created_at }` · errores `401`, `422`.

**Diseño técnico**
- **Comando:** `CreateTicketCommand(requesterId, title, description, priority, category)`
- **Handler:** `CreateTicketHandler` → `Ticket::create(...)` → `TicketRepository::save()` → despacha evento
- **Dominio:** `Ticket` (raíz); `TicketStatus::open()`; **invariante:** título obligatorio
- **Evento:** `TicketCreated(ticketId, requesterId, occurredAt)`

**Infraestructura**
- **PostgreSQL:** `DoctrineTicketRepository`. **RabbitMQ:** publica `TicketCreated` → `indexing` + `notifications`. **Redis:** invalida tag `tickets`. **ES:** indexado async por worker.

**Seguridad:** `requester_id` = usuario autenticado, nunca del body.

**Tests** · *Unit:* estado inicial `open` + evento; rechaza título vacío. *Integración:* persiste, publica, `201`/`422`. *E2E:* crear desde UI y verlo en el listado.

---

### HU-L2-E1-02 · Ver detalle de ticket

**Narrativa:** Como **usuario autorizado** quiero **ver un ticket** para **conocer su estado**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E1 |
| **Prioridad** | Must · **Estimación:** 2 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Acceso autorizado
  Dado que soy el dueño (Cliente) o un Agente/Admin
  Cuando hago GET /api/v1/tickets/{id}
  Entonces recibo el detalle del ticket

Escenario: Acceso no autorizado
  Dado que soy un Cliente que no es dueño
  Cuando hago GET /api/v1/tickets/{id}
  Entonces recibo 403 o 404 según política
```

**Contrato API** · `GET /api/v1/tickets/{id}` (autenticado) → `200 {…}` · errores `403/404`.

**Diseño técnico:** query `GetTicketQuery` → `GetTicketHandler` (modelo de lectura); autorización por propiedad (voter).

**Infraestructura:** **Redis** cache-aside `ticket:{id}` (TTL 5 min).

**Tests** · *Integración:* dueño/Agente acceden; Cliente ajeno recibe `403/404`; segunda lectura sirve de cache.

---

### HU-L2-E1-03 · Listar tickets con paginación

**Narrativa:** Como **usuario** quiero **listar tickets** para **gestionarlos**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E1 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Alcance por rol
  Dado que soy Cliente
  Cuando hago GET /api/v1/tickets
  Entonces solo veo mis tickets

Escenario: Paginación por cursor
  Cuando solicito una página con limit y cursor
  Entonces recibo data + page.next_cursor + page.has_more
```

**Contrato API** · `GET /api/v1/tickets?limit=&cursor=&status=&priority=` → `200 { data, page }`.

**Diseño técnico:** query `ListTicketsQuery` (filtros + cursor); Agente/Admin ven todo, Cliente filtra por `requester_id`.

**Infraestructura:** **Redis** cachea listados (`tickets:list:{hash}`, TTL 1 min, tag `tickets`).

**Tests** · *Integración:* Cliente solo ve los suyos; el cursor pagina correctamente.

---

### HU-L2-E1-04 · Editar ticket

**Narrativa:** Como **Cliente** quiero **editar mi ticket** para **corregir o ampliar información**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E1 |
| **Prioridad** | Should · **Estimación:** 2 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Edición autorizada
  Dado que soy el dueño o un Agente/Admin
  Y el estado permite edición
  Cuando hago PATCH /api/v1/tickets/{id}
  Entonces se actualizan título/descripción

Escenario: Datos inválidos
  Cuando envío campos inválidos
  Entonces recibo 422
```

**Contrato API** · `PATCH /api/v1/tickets/{id}` (dueño/Agente) → `200` · error `422`.

**Diseño técnico:** `EditTicketCommand` → handler; reindexado async; invalida cache.

**Infraestructura:** **RabbitMQ** (`indexing`), **Redis** (invalida `ticket:{id}` y tag `tickets`).

**Tests** · *Unit:* validación de campos. *Integración:* edición persiste e invalida cache.

---

### HU-L2-E1-05 · Transicionar estado

**Narrativa:** Como **Agente** quiero **cambiar el estado del ticket** para **reflejar su avance**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E1 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Transición válida
  Dado un ticket en "open"
  Cuando un Agente lo pasa a "in_progress"
  Entonces el estado cambia
  Y se emite TicketStatusChanged

Escenario: Transición inválida
  Dado un ticket en "closed"
  Cuando se intenta pasar a "in_progress"
  Entonces recibo 409 y el estado no cambia
```

**Contrato API** · `POST /api/v1/tickets/{id}/transitions` (rol `ROLE_AGENT`)
Request `{ "to": "in_progress" }` → `200` · error `409`.

**Diseño técnico**
- **Comando:** `ChangeTicketStatusCommand(ticketId, toStatus, actorId)`
- **Dominio:** `TicketStatus` encapsula las transiciones válidas; una inválida lanza excepción de dominio → `409`
- **Evento:** `TicketStatusChanged(ticketId, from, to, occurredAt)`

**Infraestructura:** **RabbitMQ** publica el evento (`indexing` + `notifications`); **Redis** invalida cache.

**Tests** · *Unit:* matriz de transiciones válidas/inválidas. *Integración:* `409` ante inválida; evento emitido.

---

## Épica L2-E2 · Clasificación y asignación

### HU-L2-E2-01 · Prioridad y categoría

**Narrativa:** Como **Agente** quiero **clasificar el ticket** para **priorizar el trabajo**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E2 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Reclasificar
  Cuando un Agente cambia prioridad y/o categoría
  Entonces el ticket refleja los nuevos valores

Escenario: Valores por defecto
  Dado un ticket nuevo sin clasificación
  Entonces entra como priority "medium" y category "general"
```

**Contrato API** · `PATCH /api/v1/tickets/{id}` (campos `priority`, `category`).

**Diseño técnico:** VOs `Priority` y `Category` validan el conjunto permitido; `ClassifyTicketCommand`.

**Infraestructura:** **Redis** invalida cache; **RabbitMQ** reindexa.

**Tests** · *Unit:* `Priority` rechaza valores fuera del enum.

---

### HU-L2-E2-02 · Asignar agente

**Narrativa:** Como **Agente/Admin** quiero **asignar el ticket a un agente** para **responsabilizar su gestión**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E2 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Asignación válida
  Cuando asigno el ticket a un usuario con rol Agente
  Entonces queda registrado el responsable
  Y se emite TicketAssigned

Escenario: Asignatario inválido
  Cuando intento asignar a un usuario que no es Agente
  Entonces recibo 422 y no se asigna
```

**Contrato API** · `POST /api/v1/tickets/{id}/assignment` (rol `ROLE_AGENT`)
Request `{ "assignee_id" }` → `200` · error `422`.

**Diseño técnico**
- **Comando:** `AssignTicketCommand(ticketId, assigneeId, actorId)`
- **Dominio:** **invariante:** el asignatario debe tener rol Agente (verificado vía puerto a Identity)
- **Evento:** `TicketAssigned(ticketId, assigneeId, occurredAt)` → dispara notificación (L4)

**Infraestructura:** **RabbitMQ** (`notifications` + `indexing`); **Redis** invalida cache.

**Tests** · *Integración:* asigna y emite evento; `422` si no es agente.

---

### HU-L2-E2-03 · Auto-asignación por reglas

**Narrativa:** Como **equipo** quiero **auto-asignar por carga/categoría** para **repartir el trabajo**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E2 |
| **Prioridad** | Could · **Estimación:** 5 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E2-02 |

**Criterios de aceptación**

```gherkin
Escenario: Asignación por menor carga
  Dado un ticket sin asignar
  Cuando se aplica la estrategia de auto-asignación
  Entonces se asigna al agente con menos tickets abiertos

Escenario: Sin agentes disponibles
  Dado que no hay agentes disponibles
  Entonces el ticket queda sin asignar y marcado para revisión
```

**Diseño técnico:** patrón **Strategy** (`LeastBusyAssignment`); ejecutable de forma async al crear el ticket.

**Infraestructura:** **RabbitMQ** (worker que aplica la estrategia).

**Tests** · *Unit:* la estrategia elige al de menor carga; caso sin agentes.

---

## Épica L2-E3 · Colaboración y auditoría

### HU-L2-E3-01 · Comentar en el ticket

**Narrativa:** Como **usuario autorizado** quiero **comentar** para **dar seguimiento**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E3 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Añadir comentario
  Dado que tengo acceso al ticket
  Cuando hago POST /api/v1/tickets/{id}/comments
  Entonces el comentario se añade con autor y fecha
  Y se emite TicketCommented

Escenario: Listar comentarios
  Cuando hago GET /api/v1/tickets/{id}/comments
  Entonces los recibo en orden cronológico
```

**Contrato API** · `POST` y `GET /api/v1/tickets/{id}/comments` (autorizado).

**Diseño técnico:** entidad hija `Comment` del agregado `Ticket`; `AddCommentCommand`; evento `TicketCommented`.

**Infraestructura:** **PostgreSQL** (`comments`); **RabbitMQ** (`notifications`); **Redis** invalida `ticket:{id}`.

**Tests** · *Integración:* añade y lista en orden; respeta autorización.

---

### HU-L2-E3-02 · Historial de cambios

**Narrativa:** Como **Agente/Admin** quiero **ver el historial** para **auditar el ticket**.

| Metadato | Valor |
|---|---|
| **Contexto** | Ticketing · **Épica:** L2-E3 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:tickets`, `type:story` |
| **Depende de** | HU-L2-E1-05 |

**Criterios de aceptación**

```gherkin
Escenario: Registro de auditoría
  Dado un ticket con cambios de estado y asignación
  Cuando consulto su historial
  Entonces veo cada cambio con autor y timestamp
```

**Contrato API** · `GET /api/v1/tickets/{id}/history` (Agente/Admin).

**Diseño técnico:** proyección de auditoría alimentada por los eventos de dominio (`TicketStatusChanged`, `TicketAssigned`).

**Infraestructura:** **PostgreSQL** (`ticket_history`), poblada por consumidor de eventos.

**Tests** · *Integración:* cada transición/asignación deja registro consultable.
