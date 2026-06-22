# Sistema de Gestión de Tickets — IATSAE

Monorepo de una plataforma de tickets. **Backend protagonista** (Symfony, arquitectura hexagonal + DDD) + frontend de apoyo (React). La definición completa y fuente de verdad vive en [`docs/`](./docs).

> **Estado actual:** Fase **F2 — Esqueleto del backend**. El backend Symfony 7.4 (arquitectura hexagonal, buses CQRS, walking skeleton de salud) está montado y servido por nginx en `apps/api`; el frontend (`apps/web`) se materializa en F3 (ver [`docs/plan-implementacion.md`](./docs/plan-implementacion.md)).

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
| **web** | Node 24 (build local) | `5173` | runtime Node listo |
| **postgres** | postgres:16-alpine | `5432` | `pg_isready` |
| **redis** | redis:7-alpine | `6379` | `redis-cli ping` |
| **rabbitmq** | rabbitmq:3.13-management | `5672` · `15672` (panel) | `rabbitmq-diagnostics ping` |
| **elasticsearch** | elasticsearch:8.15 | `9200` | `_cluster/health` |
| **glitchtip** | glitchtip/glitchtip | `8000` | `/_health/` |

> **Nota:** desde F2, `api-nginx` sirve la API en `http://localhost:8080/api/v1/health` (liveness) y `/api/v1/health/ready` (readiness: pinguea PostgreSQL/Redis/RabbitMQ/Elasticsearch). El contenedor `web` aún solo deja listo el runtime; su dev server de Vite se activa en F3. GlitchTip comparte la instancia de PostgreSQL con una base de datos dedicada y se apoya en un contenedor de migraciones (one-shot) y un worker de Celery.

## Comandos `make`

| Comando | Acción |
|---|---|
| `make up` | Construye y levanta el stack |
| `make down` | Detiene y elimina contenedores |
| `make down-v` | Detiene y borra volúmenes (reset total de datos) |
| `make ps` | Estado de los servicios |
| `make logs s=<servicio>` | Logs en vivo de un servicio |
| `make sh s=<servicio>` | Shell dentro de un servicio |
| `make api-install` | Instala las dependencias Composer del backend |
| `make jwt-keys` | Genera el par de claves JWT RS256 (no versionado) |
| `make test` | Smoke backend: Unit + Functional (sin servicios externos) |
| `make test-cov` | Smoke backend con informe de cobertura (PCOV) |
| `make test-integration` | Tests de integración (requiere `make up`) |
| `make lint` | PHPStan 9 + CS-Fixer + Rector + Deptrac + composer audit |
| `make lint-fix` | Autofix de estilo (CS-Fixer) y modernización (Rector) |
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
