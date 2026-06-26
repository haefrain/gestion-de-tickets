# Elasticsearch — Búsqueda

**Prueba Técnica · IATSAE** · Fase **F5 · Servicios de plataforma**

| Campo | Valor |
|---|---|
| **Estado** | Estable · v1.0 |
| **Fecha** | 2026-06-22 |
| **Versión** | Elasticsearch 8.x |
| **Sincronización** | Asíncrona por eventos (vía RabbitMQ) |

## 1. Rol

Búsqueda textual y por filtros de tickets, desacoplada del modelo de escritura (lado *query* de CQRS). El índice se mantiene al día de forma asíncrona.

## 2. Índice y mapping

Índice `tickets` (un documento por ticket):

| Campo | Tipo ES | Uso |
|---|---|---|
| `id` | keyword | identificador (UUID v7) |
| `title` | text (analizador español) | búsqueda textual |
| `description` | text (analizador español) | búsqueda textual |
| `status` | keyword | filtro |
| `priority` | keyword | filtro |
| `category` | keyword | filtro |
| `requester_id` | keyword | alcance por rol (cliente) |
| `assignee_id` | keyword | filtro por agente |
| `created_at` | date | orden y filtro por fecha |
| `updated_at` | date | orden |

> `title`/`description` se analizan para búsqueda relevante; los campos de filtro son `keyword` (coincidencia exacta).

## 3. Indexado asíncrono

1. La escritura emite el evento de dominio → RabbitMQ.
2. El worker de la cola `indexing` consume el evento.
3. Hace **upsert** del documento en `tickets` (idempotente por `id`).

El cliente ES vive tras un puerto (`SearchIndex`); la aplicación no conoce la librería concreta.

## 4. Búsqueda

- **Texto:** `multi_match` sobre `title` y `description` con relevancia.
- **Filtros:** cláusulas `term`/`terms` sobre los campos keyword.
- **Alcance por rol:** el Cliente filtra forzosamente por `requester_id`; Agente/Admin ven todo.
- **Orden:** por relevancia o por `created_at`.
- **Paginación:** `search_after` (coherente con el cursor de la API, ver [`api/api-design.md`](../api/api-design.md)).

## 5. Reindexado

- Comando CLI que reconstruye el índice desde PostgreSQL.
- **Idempotente** y reanudable; útil tras cambios de mapping o incidencias.
- Usa alias de índice para reindexar sin downtime (futuro).

## 6. Consistencia

- **Eventual:** un ticket recién creado aparece en búsqueda en ~1 s (tras procesar el evento).
- Aceptable para el MVP; si la demo lo requiere, se puede forzar `refresh` del índice puntualmente.

## 7. Pendiente de iterar

- Confirmar versión menor de ES 8.x y cliente PHP (oficial vs Elastica).
- Afinar el analizador (sinónimos, stemming en español).
- Decidir campos adicionales buscables (comentarios).
