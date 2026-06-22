# Backend (Symfony) — Reglas · apps/api

Arquitectura **hexagonal** estricta. Ver raíz `../../CLAUDE.md` y `../../docs/architecture/`.

## Capas y regla de dependencia

- `Domain/` — entidades, value objects, agregados, eventos de dominio. **Sin** Symfony/Doctrine.
- `Application/` — casos de uso, **puertos** (interfaces), command/query handlers.
- `Infrastructure/` — controllers HTTP, adaptadores (Doctrine, Redis, ES, RabbitMQ), JWT.
- Dependencias **hacia adentro**: Infrastructure → Application → Domain.

## Organización

- Un módulo por bounded context: `Identity/`, `Ticketing/`, `Search/`, `Notifications/`, más `Shared/`.

## Reglas

- **Persistencia:** dominio puro + mapeo Doctrine en Infrastructure (XML, sin atributos en el dominio).
- **IDs:** UUID v7 (`symfony/uid`).
- **Escritura:** `*Command` + `*Handler`. **Lectura:** `*Query` + `*Handler`.
- **Eventos de dominio** → Messenger → RabbitMQ (async); consumidores **idempotentes** por `TicketId`.
- **Errores de dominio** → respuesta HTTP **RFC 7807**.
- Invariantes dentro del agregado (nada de dominio anémico).

## Comandos

- `composer install` · `vendor/bin/phpstan analyse` (nivel 9) · `vendor/bin/phpunit`.

## Calidad

- PHPStan 9 + CS-Fixer + Rector; cobertura ≥ 80% en `Domain`/`Application`.
- Detalle de patrones: `../../docs/architecture/patterns.md`.
