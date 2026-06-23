# Sistema de Gestión de Tickets — IATSAE

[![CI](https://github.com/haefrain/gestion-de-tickets/actions/workflows/ci.yml/badge.svg)](https://github.com/haefrain/gestion-de-tickets/actions/workflows/ci.yml)

Monorepo de una plataforma de gestión de tickets. **Backend protagonista** (Symfony 7.4, arquitectura **hexagonal + DDD + CQRS**) y frontend de apoyo (React 19). La definición completa y fuente de verdad vive en [`docs/`](./docs).

> **Estado:** las **38 HU del backlog** (23 *Must* + 11 *Should* + 4 *Could*) están implementadas e integradas a `main` vía Pull Request, con el **CI bloqueante en verde** (lint · análisis estático · tests · cobertura ≥80% en dominio/aplicación · build · E2E). `main` está protegida: no se fusiona sin el pipeline verde.

---

## 📑 Índice

- [Qué hace el sistema](#-qué-hace-el-sistema)
- [Stack](#-stack)
- [Arranque rápido](#-arranque-rápido)
- [Servicios del stack](#-servicios-del-stack)
- [Comandos `make`](#-comandos-make)
- [Estructura del repositorio](#-estructura-del-repositorio)
- [Calidad automatizada (CI)](#-calidad-automatizada-ci)
- [Documentación completa](#-documentación-completa)

---

## ✨ Qué hace el sistema

| Capacidad | Detalle |
|---|---|
| **Identidad y acceso** | Registro y login con **JWT (RS256)**, refresh token en cookie HttpOnly, **RBAC** (cliente / agente / admin), gestión de usuarios por admin, perfil propio. |
| **Tickets** | Ciclo de vida completo (crear, ver, listar con paginación por cursor), máquina de estados, prioridad y categoría, asignación de agente (manual y auto-asignación al menos cargado). |
| **Colaboración** | Comentarios e historial de auditoría por ticket. |
| **Búsqueda** | Full-text en **Elasticsearch** con filtros combinados y alcance por rol; indexado asíncrono. |
| **Asincronía** | Eventos de dominio por **RabbitMQ** + worker (reintentos con backoff + DLQ); notificaciones por email ante asignación y cambio de estado. |
| **Rendimiento y seguridad** | Cache-aside en **Redis** con invalidación, rate limiting en login, errores **RFC 7807**. |
| **Frontend** | SPA React 19 + MUI + TanStack Query: auth, listado/detalle de tickets, comentarios, historial, admin, perfil, búsqueda con filtros. |
| **Observabilidad** | Captura de errores no controlados en **GlitchTip** (compatible con Sentry). |

## 🧰 Stack

- **Backend:** PHP 8.4 · Symfony 7.4 LTS · PostgreSQL 16 · Doctrine (solo en infraestructura) · JWT RS256.
- **Plataforma:** Redis (cache + rate limit) · RabbitMQ (Symfony Messenger) · Elasticsearch 8.x · GlitchTip.
- **Frontend:** React 19 · TypeScript (strict) · Vite · MUI · TanStack Query · Storybook.
- **Calidad:** PHPStan 9 · CS-Fixer · Rector · Deptrac · PHPUnit · ESLint · Prettier · Vitest · Playwright.

## 🚀 Arranque rápido

Solo se necesita **Docker + Docker Compose** y **`make`** (no hace falta PHP ni Node en la máquina: todo corre en contenedores).

```bash
make up      # construye imágenes, instala dependencias y levanta el stack
make ps      # verifica que todos los servicios estén "healthy"
make seed    # carga datos de demo (usuarios por rol + tickets de ejemplo)
```

Una vez arriba:

| Recurso | URL |
|---|---|
| **Aplicación web** | http://localhost:5173 |
| **API** | http://localhost:8080/api/v1 |
| **Health / Readiness** | `/api/v1/health` · `/api/v1/health/ready` |
| **RabbitMQ (panel)** | http://localhost:15672 |
| **GlitchTip** | http://localhost:8000 |
| **Storybook** | `make storybook` → http://localhost:6006 |

**Usuarios de demo** (tras `make seed`, contraseña común `Demo1234`):

| Rol | Email |
|---|---|
| Cliente | `cliente@demo.local` |
| Agente | `agente@demo.local` |
| Admin | `admin@demo.local` |

> Para personalizar puertos/credenciales: `cp .env.example .env` y editá lo que necesites.

## 🐳 Servicios del stack

| Servicio | Imagen | Puerto local | Healthcheck |
|---|---|---|---|
| **api** | PHP 8.4-FPM (build local) | — (FastCGI 9000) | runtime PHP listo |
| **api-nginx** | nginx 1.27-alpine | `8080` | `GET /api/v1/health` |
| **web** | Node 24 (build local) | `5173` | dev server de Vite |
| **worker** | PHP 8.4 (build local) | — | consume `async_events` |
| **postgres** | postgres:16-alpine | `5432` | `pg_isready` |
| **redis** | redis:7-alpine | `6379` | `redis-cli ping` |
| **rabbitmq** | rabbitmq:3.13-management | `5672` · `15672` | `rabbitmq-diagnostics ping` |
| **elasticsearch** | elasticsearch:8.15 | `9200` | `_cluster/health` |
| **glitchtip** | glitchtip/glitchtip | `8000` | `/_health/` |

## ⚙️ Comandos `make`

| Comando | Acción |
|---|---|
| `make up` / `make down` | Levanta / detiene el stack |
| `make down-v` | Detiene y borra volúmenes (reset total de datos) |
| `make ps` · `make logs s=<svc>` · `make sh s=<svc>` | Estado · logs · shell de un servicio |
| `make seed` | Carga datos de demo (requiere el stack arriba) |
| `make test` | Tests de backend + frontend |
| `make test-api` / `make test-web` / `make test-integration` | Tests por lado (la integración requiere el stack arriba) |
| `make lint` / `make lint-fix` | Análisis estático / autofix (backend + frontend) |
| `make jwt-keys` | Genera el par de claves JWT RS256 (no versionado) |
| `make worker` | Consume eventos async en primer plano (debug) |
| `make storybook` / `make build-web` | Storybook / build de producción del frontend |

## 🗂️ Estructura del repositorio

```
.
├── apps/
│   ├── api/              # Backend Symfony (hexagonal + DDD + CQRS)
│   │   └── src/<Contexto>/{Domain,Application,Infrastructure}
│   └── web/              # Frontend React (features + design system)
├── docker/               # Dockerfiles + init de PostgreSQL
├── docs/                 # Definición completa (fuente de verdad)
├── docker-compose.yml · Makefile · .env.example
```

El backend se organiza **por bounded context** (`Identity`, `Ticketing`, `Search`, `Notifications`, `Shared`), cada uno con sus tres capas. El *porqué* de esta y otras decisiones está en [`docs/architecture/decisiones-de-diseno.md`](./docs/architecture/decisiones-de-diseno.md).

## ✅ Calidad automatizada (CI)

Cada Pull Request a `main` dispara [GitHub Actions](.github/workflows/ci.yml) con cuatro jobs **bloqueantes**:

- **commitlint** — Conventional Commits.
- **backend** — PHPStan 9 · CS-Fixer · Rector · Deptrac (límites hexagonales) · `composer audit` · PHPUnit + **gate de cobertura ≥80%** en dominio/aplicación.
- **frontend** — Prettier · ESLint · TypeScript strict · Vitest · build · Storybook.
- **e2e** — Playwright sobre el build de producción.

**Pre-commit (opcional, requiere Node en el host):** `npm install` en la raíz activa los hooks de Husky (`commit-msg` valida Conventional Commits; `pre-commit` formatea con Prettier).

## 📚 Documentación completa

Toda la información del proyecto vive en [`docs/`](./docs). Punto de partida según lo que busques:

### Producto y alcance
- [Visión del producto](./docs/00-vision.md) — qué se construye, alcance del MVP y futuro.
- [Glosario](./docs/01-glosario.md) — lenguaje ubicuo del dominio.
- [Backlog (38 HU)](./docs/product/backlog.md) — índice de historias de usuario · [plantilla de ficha](./docs/product/ejemplo-ficha-hu.md).
- HU detalladas por legenda: [L1 Identidad](./docs/product/legends/L1-identidad.md) · [L2 Tickets](./docs/product/legends/L2-tickets.md) · [L3 Búsqueda](./docs/product/legends/L3-busqueda.md) · [L4 Notificaciones](./docs/product/legends/L4-notificaciones.md) · [L5 Cache](./docs/product/legends/L5-cache.md) · [L6 Frontend](./docs/product/legends/L6-frontend.md) · [L7 Plataforma](./docs/product/legends/L7-plataforma.md).

### Arquitectura y decisiones
- [Arquitectura — overview](./docs/architecture/overview.md) — estilo, bounded contexts, capas, modelo de dominio, CQRS.
- [**Decisiones de diseño y sus motivos**](./docs/architecture/decisiones-de-diseno.md) — el *por qué* de cada decisión del sistema.
- [Catálogo de patrones](./docs/architecture/patterns.md) — patrones aplicados con su caso real y su anti-caso.
- [ADRs](./docs/architecture/adr/) — decisiones de arquitectura registradas (0001–0007).

### API, plataforma y seguridad
- [Diseño de la API](./docs/api/api-design.md) — REST `/api/v1`, errores RFC 7807, paginación por cursor.
- [Seguridad](./docs/security.md) — JWT, RBAC, hardening.
- Plataforma: [Elasticsearch](./docs/platform/elasticsearch.md) · [RabbitMQ](./docs/platform/rabbitmq.md) · [Redis](./docs/platform/redis.md).

### Calidad y frontend
- Calidad: [Testing](./docs/quality/testing.md) · [Linting / análisis estático](./docs/quality/linting.md) · [CI/CD](./docs/quality/ci-cd.md).
- Design system: [overview](./docs/design-system/overview.md) · [especificación de Storybook](./docs/design-system/storybook-spec.md).

### Proceso
- [Plan de implementación por fases](./docs/plan-implementacion.md) — hoja de ruta F0–F8.
- [ROADMAP](./ROADMAP.md) — visión general de la evolución.
- [DevEx con Claude Code](./docs/ai-devex/overview.md) — configuración de tooling del repo.
