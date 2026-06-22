# ADR 0004 · Dominio puro desacoplado de Doctrine

- **Estado:** Aceptada · 2026-06-22

## Contexto

Doctrine es el ORM estándar de Symfony, pero anotar las entidades de dominio con su mapeo acopla el núcleo a la infraestructura de persistencia.

## Decisión

Mantener el **dominio puro**: entidades y value objects sin dependencias de Doctrine. La persistencia se define como **puerto** (`TicketRepository`) en `Application` y se implementa en `Infrastructure` con un adaptador Doctrine que **mapea** entre el modelo de dominio y el modelo de persistencia (mapeo por XML, sin atributos en el dominio).

## Consecuencias

- (+) Dominio testeable en memoria y libre de infraestructura.
- (+) El ORM es reemplazable sin tocar el dominio.
- (−) Mayor esfuerzo: mapeo explícito y, posiblemente, entidades de persistencia separadas.

## Alternativas consideradas

- **Entidades de dominio con atributos Doctrine:** más simple, pero acopla dominio e infraestructura.
- **Sin ORM (DBAL/SQL):** máximo control pero mucho código repetitivo.
