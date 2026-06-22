---
name: crear-caso-uso-hexagonal
description: Genera el andamiaje de un caso de uso hexagonal (Command/Query + Handler + puerto + adaptador) dentro de un bounded context del backend, respetando la regla de dependencia hacia adentro. Úsalo al implementar una HU de escritura o lectura en apps/api.
---

# Crear caso de uso hexagonal

Genera la estructura de un caso de uso en `apps/api/src/<Contexto>/` respetando `apps/api/CLAUDE.md` y `docs/architecture/`.

## Entradas que necesitas

- **Contexto:** `Identity` · `Ticketing` · `Search` · `Notifications`.
- **Tipo:** escritura (`Command`) o lectura (`Query`).
- **Nombre del caso de uso** (verbo + sustantivo), p. ej. `CrearTicket`, `TransicionarEstado`, `ListarTickets`.

## Qué generar (CQRS ligero)

**Escritura — `Command`:**
1. `Application/Command/<Nombre>Command.php` — DTO inmutable con los datos de entrada (UUID v7 donde aplique).
2. `Application/Command/<Nombre>Handler.php` — orquesta el caso de uso; depende solo de **puertos** (interfaces).
3. `Application/Port/<Algo>Repository.php` (u otros puertos) — interfaz en Application.
4. `Domain/...` — invariantes y mutaciones en el agregado (constructor nombrado, Value Objects). Emite eventos de dominio (`TicketCreated`, …) cuando corresponda.
5. `Infrastructure/Persistence/Doctrine/Doctrine<Algo>Repository.php` — adaptador que implementa el puerto (mapeo XML, sin atributos en el dominio).

**Lectura — `Query`:** análogo con `Query` + `QueryHandler`, resolviendo contra el modelo de lectura (Doctrine/ES/Redis) sin tocar el modelo de escritura.

## Reglas

- `Domain/` y `Application/` **no** importan Symfony ni Doctrine.
- Registra el handler en el bus de Messenger correspondiente (`config/packages/messenger.yaml`).
- Genera siempre el esqueleto de test unitario del dominio y de integración del handler (delegando al agente `tests`).

## Verificación

`make lint` (PHPStan 9 sin errores) y el test de humo del caso de uso en verde.
