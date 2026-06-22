# Sistema de Gestión de Tickets — Reglas del proyecto

Monorepo de una plataforma de tickets. **Backend protagonista** (Symfony) + frontend de apoyo (React). La **fuente de verdad** es `docs/`; consúltala antes de codificar.

## Arquitectura (no negociable)

- **Hexagonal** (Puertos y Adaptadores) + **DDD táctico** + **monolito modular**.
- 4 bounded contexts: **Identity · Ticketing · Search · Notifications**, comunicados por **eventos de dominio** (RabbitMQ).
- **CQRS ligero**: comandos para escritura, queries para lectura.
- El **dominio es puro**: nunca importa Symfony ni Doctrine. Framework e infraestructura viven solo en `Infrastructure`.
- Detalle: `docs/architecture/overview.md` · decisiones en `docs/architecture/adr/`.

## Stack

- **Backend:** PHP 8.4 · Symfony 7.4 LTS · PostgreSQL · Doctrine (solo en infraestructura).
- **Plataforma:** Redis (cache-aside) · RabbitMQ (Symfony Messenger) · Elasticsearch 8.x · JWT (RS256).
- **Frontend:** React 19 · TypeScript · Vite · MUI · TanStack Query.
- **IDs:** UUID v7. **Errores (local):** GlitchTip.

## Estructura

- `apps/api` — backend (ver `apps/api/CLAUDE.md`).
- `apps/web` — frontend (ver `apps/web/CLAUDE.md`).
- `docs/` — definición completa. `docs/product/` — backlog (38 HU con ficha técnica).

## Comandos

- `make up` / `make down` — levantar/parar el stack (Docker).
- `make test` — tests (unit + integración + E2E).
- `make lint` — linters y análisis estático.
- `make seed` — datos de demo.

## Calidad (estricto desde el día 1)

- **PHP:** PHPStan **nivel 9** + CS-Fixer (PSR-12 + Symfony) + Rector.
- **TS:** `strict: true` + ESLint type-checked + Prettier.
- **Tests:** cobertura **≥ 80%** en dominio/aplicación; bloqueante en CI.
- Detalle: `docs/quality/`.

## Convenciones

- **Conventional Commits** (`feat:`, `fix:`, `docs:`…).
- Decisiones de arquitectura como **ADR** en `docs/architecture/adr/`.
- API REST `/api/v1`, errores **RFC 7807**, paginación por **cursor**. Ver `docs/api/api-design.md` y `docs/security.md`.

## Reglas de oro

- No acoplar el dominio al framework.
- Aplicar un patrón solo si resuelve un problema real (`docs/architecture/patterns.md`).
- Nada se implementa sin su HU: respeta criterios de aceptación y DoD del backlog.
- **Primer corte = HU `Must` (23).** `Should`/`Could` quedan para la 2ª iteración.
- **Arranque por fases:** sigue `docs/plan-implementacion.md` — primero las bases (F0–F4: entorno Docker, config `.claude/`, esqueletos, CI), luego las HU. No se desarrollan features sin las bases verdes.
