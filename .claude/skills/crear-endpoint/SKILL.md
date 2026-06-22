---
name: crear-endpoint
description: Genera el andamiaje de un endpoint REST (Controller + DTO de entrada/salida + ruta + esqueleto de test) en /api/v1, conectado a un caso de uso vía los buses. Úsalo al exponer una operación de una HU en apps/api.
---

# Crear endpoint + contrato

Genera un endpoint en `apps/api/src/<Contexto>/Infrastructure/Http/` siguiendo `docs/api/api-design.md` y `docs/security.md`.

## Entradas que necesitas

- **Contexto** y **caso de uso** que expone (debe existir su Command/Query + Handler; si no, usa `crear-caso-uso-hexagonal` primero).
- **Método y ruta** bajo `/api/v1` (p. ej. `POST /api/v1/tickets`).
- **Rol(es)** autorizados y reglas de propiedad.

## Qué generar

1. **Controller** delgado: valida el DTO de entrada, despacha el `Command`/`Query` por el bus, mapea el resultado al DTO de salida. Sin lógica de dominio.
2. **DTO de entrada** con validación (Symfony Validator) y **DTO/serialización de salida**.
3. **Ruta** en `config/routes.yaml` (o atributos de routing en Infrastructure), versionada en `/api/v1`.
4. **Autorización:** `#[IsGranted(...)]` y/o **Voter** cuando haya reglas de propiedad (un cliente solo sus tickets).
5. **Errores RFC 7807:** las excepciones de dominio se traducen a `application/problem+json`.
6. **Paginación por cursor** en listados (cursor opaco), según `api-design.md`.
7. **Esqueleto de test** funcional/E2E del endpoint (códigos 2xx y de error: 401/403/404/409/422).

## Verificación

`make lint` verde y el test del endpoint cubriendo el caso feliz y al menos un caso de error.
