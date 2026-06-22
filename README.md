# Sistema de Gestión de Tickets — IATSAE

Monorepo de una plataforma de tickets. **Backend protagonista** (Symfony, arquitectura hexagonal + DDD) + frontend de apoyo (React). La definición completa y fuente de verdad vive en [`docs/`](./docs).

> **Estado actual:** Fase **F0 — Fundación (Docker)**. El stack de infraestructura se levanta con un solo comando. El código de aplicación (`apps/api`, `apps/web`) se materializa en fases posteriores (ver [`docs/plan-implementacion.md`](./docs/plan-implementacion.md)).

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
| **web** | Node 24 (build local) | `5173` | runtime Node listo |
| **postgres** | postgres:16-alpine | `5432` | `pg_isready` |
| **redis** | redis:7-alpine | `6379` | `redis-cli ping` |
| **rabbitmq** | rabbitmq:3.13-management | `5672` · `15672` (panel) | `rabbitmq-diagnostics ping` |
| **elasticsearch** | elasticsearch:8.15 | `9200` | `_cluster/health` |
| **glitchtip** | glitchtip/glitchtip | `8000` | `/_health/` |

> **Nota F0:** los contenedores `api` y `web` solo dejan listo el runtime (su healthcheck de aplicación —`GET /api/v1/health` y el dev server de Vite— se activan en F2 y F3 respectivamente). GlitchTip comparte la instancia de PostgreSQL con una base de datos dedicada y se apoya en un contenedor de migraciones (one-shot) y un worker de Celery.

## Comandos `make`

| Comando | Acción |
|---|---|
| `make up` | Construye y levanta el stack |
| `make down` | Detiene y elimina contenedores |
| `make down-v` | Detiene y borra volúmenes (reset total de datos) |
| `make ps` | Estado de los servicios |
| `make logs s=<servicio>` | Logs en vivo de un servicio |
| `make sh s=<servicio>` | Shell dentro de un servicio |
| `make test` | Tests (se implementa en F2/F3) |
| `make lint` | Linters y análisis estático (F2/F3) |
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
