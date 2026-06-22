# ADR 0002 · Cuatro bounded contexts comunicados por eventos

- **Estado:** Aceptada · 2026-06-22

## Contexto

El dominio mezcla identidad, gestión de tickets, búsqueda y notificaciones. Combinarlos en un solo modelo genera acoplamiento y un agregado sobrecargado.

## Decisión

Separar en cuatro contextos: **Identity**, **Ticketing** (núcleo), **Search** y **Notifications**. Ticketing emite **eventos de dominio**; Search y Notifications reaccionan a ellos de forma asíncrona. Identity es *upstream* y provee identidad/roles.

## Consecuencias

- (+) Cada contexto evoluciona y se prueba por separado.
- (+) Search y Notifications no acoplan al núcleo; se añaden consumidores sin tocar Ticketing.
- (−) Requiere infraestructura de eventos (RabbitMQ/Messenger) desde el inicio.

## Alternativas consideradas

- **Un único contexto:** más simple, pero acopla búsqueda y notificaciones al núcleo y dificulta la asincronía.
- **Llamadas síncronas entre contextos:** acoplan y penalizan la latencia del request.
