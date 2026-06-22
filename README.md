# Sistema de Gestión de Tickets — IATSAE

Monorepo de una plataforma de tickets. **Backend protagonista** (Symfony, arquitectura hexagonal + DDD) + frontend de apoyo (React). La definición completa y fuente de verdad vive en [`docs/`](./docs).

> **Estado actual:** Fase **F3 — Esqueleto del frontend**. El backend Symfony 7.4 (hexagonal, CQRS, walking skeleton) corre en `apps/api` (servido por nginx); el frontend React 19 + MUI + Storybook en `apps/web` aporta el design system reutilizable (tema índigo claro/oscuro, layout, moléculas y organismos) con su galería de Storybook. El cableado de datos con la API (TanStack Query) llega en F6 (ver [`docs/plan-implementacion.md`](./docs/plan-implementacion.md)).

## Requisitos

- **Docker** + **Docker Compose** (probado con [OrbStack](https://orbstack.dev) en macOS).
- `make`.

No se necesita PHP, Node ni ninguna toolchain instalada en la máquina: todo corre en contenedores.

## Arranque rápido

```bash
make up      # construye imágenes y levanta el stack en segundo plano
make ps      # verifica que todos los servicios estén "healthy"
make down    # detiene el stack
```

`make up` funciona sin configuración previa (el `docker-compose.yml` trae valores por defecto). Para personalizar puertos/credenciales:

```bash
cp .env.example .env   # opcional: edita los valores que necesites
```

## Servicios del stack

| Servicio | Imagen | Puerto local | Healthcheck |
|---|---|---|---|
| **api** | PHP 8.4-FPM (build local) | — (FastCGI 9000) | runtime PHP listo |
| **api-nginx** | nginx 1.27-alpine | `8080` | `GET /api/v1/health` |
| **web** | Node 24 (build local) | `5173` | dev server de Vite (React 19) |
| **postgres** | postgres:16-alpine | `5432` | `pg_isready` |
| **redis** | redis:7-alpine | `6379` | `redis-cli ping` |
| **rabbitmq** | rabbitmq:3.13-management | `5672` · `15672` (panel) | `rabbitmq-diagnostics ping` |
| **elasticsearch** | elasticsearch:8.15 | `9200` | `_cluster/health` |
| **glitchtip** | glitchtip/glitchtip | `8000` | `/_health/` |

> **Nota:** `api-nginx` sirve la API en `http://localhost:8080/api/v1/health` (liveness) y `/api/v1/health/ready` (readiness: pinguea PostgreSQL/Redis/RabbitMQ/Elasticsearch). Desde F3, `web` sirve la SPA en `http://localhost:5173` (Vite) y el Storybook del design system se levanta con `make storybook` (`http://localhost:6006`). GlitchTip comparte la instancia de PostgreSQL con una base de datos dedicada y se apoya en un contenedor de migraciones (one-shot) y un worker de Celery.

## Comandos `make`

| Comando | Acción |
|---|---|
| `make up` | Construye y levanta el stack |
| `make down` | Detiene y elimina contenedores |
| `make down-v` | Detiene y borra volúmenes (reset total de datos) |
| `make ps` | Estado de los servicios |
| `make logs s=<servicio>` | Logs en vivo de un servicio |
| `make sh s=<servicio>` | Shell dentro de un servicio |
| `make test` | Tests de **backend + frontend** |
| `make lint` | Análisis estático de **backend + frontend** |
| `make lint-fix` | Autofix de backend (CS-Fixer/Rector) + frontend (Prettier) |
| `make storybook` | Levanta Storybook del frontend (`http://localhost:6006`) |
| `make api-install` / `make web-install` | Instala dependencias de backend / frontend |
| `make jwt-keys` | Genera el par de claves JWT RS256 (no versionado) |
| `make test-api` / `make test-web` | Tests solo de backend / frontend |
| `make lint-api` / `make lint-web` | Lint solo de backend / frontend |
| `make test-integration` | Tests de integración del backend (requiere `make up`) |
| `make build-web` | Build de producción del frontend |
| `make seed` | Datos de demo (F6) |

## Estructura

```
.
├── apps/
│   ├── api/          # Backend Symfony (F2)
│   └── web/          # Frontend React (F3)
├── docker/
│   ├── Dockerfile.api
│   ├── Dockerfile.web
│   └── postgres/init/   # init SQL (BD de GlitchTip)
├── docs/             # Definición completa (fuente de verdad)
├── docker-compose.yml
├── Makefile
└── .env.example
```

## Documentación

- **Plan de implementación por fases:** [`docs/plan-implementacion.md`](./docs/plan-implementacion.md)
- **Arquitectura:** [`docs/architecture/overview.md`](./docs/architecture/overview.md) · ADRs en [`docs/architecture/adr/`](./docs/architecture/adr)
- **Backlog (38 HU):** [`docs/product/backlog.md`](./docs/product/backlog.md)
- **API · Seguridad · Calidad:** [`docs/api/`](./docs/api) · [`docs/security.md`](./docs/security.md) · [`docs/quality/`](./docs/quality)
