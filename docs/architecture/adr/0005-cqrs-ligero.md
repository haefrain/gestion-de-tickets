# ADR 0005 · CQRS ligero (comandos / consultas)

- **Estado:** Aceptada · 2026-06-22

## Contexto

Las lecturas (listados, búsqueda) y las escrituras (crear, transicionar, asignar) tienen necesidades distintas: las lecturas piden velocidad y proyecciones; las escrituras, invariantes y consistencia.

## Decisión

Aplicar **CQRS ligero**: separar **comandos** (escritura, vía CommandBus → handlers → dominio) de **consultas** (lectura, vía QueryBus → modelos de lectura). La búsqueda lee de **Elasticsearch**; los listados pueden apoyarse en **Redis**. No se introduce *event sourcing*.

## Consecuencias

- (+) Lecturas optimizables (ES/Redis) sin contaminar el modelo de escritura.
- (+) Handlers pequeños y enfocados (SRP).
- (−) Más clases (un handler por comando/consulta) y duplicación de DTOs.

## Alternativas consideradas

- **Modelo único (CRUD):** menos clases, pero mezcla lectura/escritura y limita la optimización.
- **CQRS + Event Sourcing:** potente pero excesivo para el alcance y el plazo.
