# L3 · Búsqueda y Descubrimiento

**Backlog rico** · Bounded context **Search** · [↩ índice](../backlog.md)

> Indexado y búsqueda de tickets con Elasticsearch 8.x, sincronizado de forma **asíncrona** con PostgreSQL vía eventos de dominio. Lado *query* de CQRS; puerto `SearchIndex`.

---

## Épica L3-E1 · Indexado

### HU-L3-E1-01 · Indexar ticket en cambios

**Narrativa:** Como **sistema** quiero **indexar el ticket al crear/actualizar** para **mantener la búsqueda al día**.

| Metadato | Valor |
|---|---|
| **Contexto** | Search · **Épica:** L3-E1 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:search`, `type:story` |
| **Depende de** | HU-L2-E1-01, HU-L4-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Indexado tras evento
  Dado que se emite TicketCreated o TicketStatusChanged
  Cuando el worker de "indexing" consume el evento
  Entonces el documento se hace upsert en el índice "tickets"

Escenario: Idempotencia
  Dado el mismo evento procesado dos veces
  Entonces el documento no se duplica
```

**Diseño técnico:** consumidor `IndexTicketHandler` escucha los eventos de Ticketing; usa el puerto `SearchIndex::upsert(TicketDocument)`. Mapping: `title`/`description` (text, analizador español), resto `keyword`/`date`.

**Infraestructura:** **RabbitMQ** (cola `indexing`); **Elasticsearch** (índice `tickets`).

**Seguridad:** el documento guarda `requester_id` para el alcance por rol en la búsqueda.

**Tests** · *Integración:* tras el evento, el ticket es buscable; reprocesar no duplica.

---

### HU-L3-E1-02 · Reindexado completo

**Narrativa:** Como **Admin** quiero **reindexar todo** para **recuperar consistencia tras incidencias**.

| Metadato | Valor |
|---|---|
| **Contexto** | Search · **Épica:** L3-E1 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:search`, `type:story` |
| **Depende de** | HU-L3-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Reconstrucción del índice
  Dado el comando de reindexado
  Cuando se ejecuta
  Entonces el índice se reconstruye desde PostgreSQL
  Y el proceso es idempotente y reporta progreso
```

**Diseño técnico:** comando CLI `search:reindex`; lee por lotes desde el repositorio y hace upsert; usa alias de índice para evitar downtime (futuro).

**Infraestructura:** **PostgreSQL** (origen) → **Elasticsearch** (destino).

**Tests** · *Integración:* tras vaciar el índice, el reindexado lo reconstruye completo.

---

## Épica L3-E2 · Búsqueda y filtros

### HU-L3-E2-01 · Buscar por texto

**Narrativa:** Como **Agente** quiero **buscar tickets por texto** para **encontrarlos rápido**.

| Metadato | Valor |
|---|---|
| **Contexto** | Search · **Épica:** L3-E2 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:search`, `type:story` |
| **Depende de** | HU-L3-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Búsqueda relevante
  Cuando hago GET /api/v1/search/tickets?q=login
  Entonces recibo tickets cuyo título/descripción coinciden, por relevancia

Escenario: Alcance por rol
  Dado que soy Cliente
  Entonces la búsqueda solo devuelve mis tickets
```

**Contrato API** · `GET /api/v1/search/tickets?q=&cursor=&limit=` → `200 { data, page }`.

**Diseño técnico:** query `SearchTicketsQuery`; `multi_match` sobre `title`/`description`; el Cliente añade filtro forzado `requester_id`.

**Infraestructura:** **Elasticsearch** (consulta); paginación `search_after`.

**Tests** · *Integración:* relevancia correcta; el Cliente no ve tickets ajenos.

---

### HU-L3-E2-02 · Filtros combinados y orden

**Narrativa:** Como **Agente** quiero **filtrar por estado/prioridad/agente/fecha** para **acotar resultados**.

| Metadato | Valor |
|---|---|
| **Contexto** | Search · **Épica:** L3-E2 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:search`, `type:story` |
| **Depende de** | HU-L3-E2-01 |

**Criterios de aceptación**

```gherkin
Escenario: Filtros + texto
  Cuando combino q con status, priority, assignee y rango de fecha
  Entonces los resultados respetan todos los filtros

Escenario: Orden
  Cuando ordeno por relevancia o por created_at
  Entonces los resultados llegan en ese orden, paginados
```

**Contrato API** · `GET /api/v1/search/tickets?q=&status=&priority=&assignee=&from=&to=&sort=`.

**Diseño técnico:** patrón **Specification** para componer filtros `term`/`range`; combinables con la consulta textual.

**Infraestructura:** **Elasticsearch** (`bool` query con `must`/`filter`).

**Tests** · *Integración:* cada filtro acota; el orden y el cursor funcionan combinados.
