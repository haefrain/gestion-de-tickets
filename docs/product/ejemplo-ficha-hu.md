# Muestra — Ficha rica de HU

**Prueba Técnica · IATSAE** · Fase **F1 · Producto** (iteración)

> Plantilla propuesta para enriquecer **cada** HU del backlog con una **ficha técnica completa**, lista para que Claude Code implemente sin ambigüedad. Incorpora las decisiones de F2–F8. Valida este formato; al aprobarlo, lo aplico a las 38 HU.

---

## HU-L2-E1-01 · Crear ticket

**Narrativa:** Como **Cliente** quiero **crear un ticket** para **reportar una incidencia**.

| Metadato | Valor |
|---|---|
| **Bounded context** | Ticketing |
| **Épica** | L2-E1 · Ciclo de vida del ticket |
| **Prioridad** | Must · **Estimación:** 3 |
| **Labels** | `area:tickets`, `type:story` |
| **Depende de** | HU-L1-E1-02 (login con JWT) |

### Criterios de aceptación (Gherkin)

```gherkin
Escenario: Creación exitosa
  Dado que estoy autenticado como Cliente
  Y envío un título y una descripción válidos
  Cuando hago POST /api/v1/tickets
  Entonces el ticket se crea en estado "open" asociado a mí
  Y recibo 201 con el recurso creado
  Y se emite el evento de dominio TicketCreated

Escenario: Datos inválidos
  Dado que estoy autenticado como Cliente
  Y el título está vacío
  Cuando hago POST /api/v1/tickets
  Entonces recibo 422 con cuerpo application/problem+json
  Y el ticket no se crea

Escenario: No autenticado
  Dado que no envío un JWT válido
  Cuando hago POST /api/v1/tickets
  Entonces recibo 401
```

### Contrato de API

| Aspecto | Detalle |
|---|---|
| **Endpoint** | `POST /api/v1/tickets` |
| **Auth** | `Authorization: Bearer <access_token>` · rol `ROLE_CLIENT` |
| **Request** | `{ "title": string, "description": string, "priority"?: low\|medium\|high\|urgent, "category"?: string }` |
| **201** | `{ "id", "title", "status": "open", "priority", "category", "requester_id", "assignee_id": null, "created_at" }` |
| **Errores** | `401` no autenticado · `422` validación (RFC 7807) |

### Diseño técnico (hexagonal + CQRS)

| Elemento | Definición |
|---|---|
| **Comando** | `CreateTicketCommand(requesterId, title, description, priority, category)` |
| **Handler** | `CreateTicketHandler` (Application): valida, llama a `Ticket::create(...)`, persiste vía puerto y despacha el evento |
| **Dominio** | Agregado `Ticket`; VOs `TicketId` (UUID v7), `TicketStatus::open()`, `Priority`, `Category`; **invariante:** título obligatorio |
| **Evento** | `TicketCreated(ticketId, requesterId, occurredAt)` |
| **Puerto** | `TicketRepository::save(Ticket): void` |

### Infraestructura implicada

| Servicio | Acción |
|---|---|
| **PostgreSQL** | `DoctrineTicketRepository` persiste el agregado (mapeo en infraestructura) |
| **RabbitMQ** | Publica `TicketCreated` en el exchange `tickets` → colas `indexing` y `notifications` |
| **Redis** | Invalida la tag de cache `tickets` (listados afectados) |
| **Elasticsearch** | Indexado **asíncrono** por el worker de `indexing` (fuera del request) |

### Seguridad

- Requiere JWT válido; rol mínimo `ROLE_CLIENT`.
- `requester_id` se toma del usuario autenticado, **nunca** del cuerpo de la petición.

### Tests esperados

| Nivel | Caso |
|---|---|
| **Unit** | `Ticket::create` fija estado `open` y registra `TicketCreated`; rechaza título vacío |
| **Integración** | `POST` persiste en BD, publica el evento y responde 201; 422 si falta el título |
| **E2E** | El Cliente crea un ticket desde la UI y lo ve en su listado |

### Definición de Hecho (DoD)

- Criterios de aceptación cumplidos y tests en verde (cobertura de dominio/aplicación ≥ 80%).
- Linters y análisis estático sin violaciones (PHPStan 9, TS strict).
- Endpoint reflejado en la guía de API; evento documentado.
- Ejecutable vía `docker-compose`.
