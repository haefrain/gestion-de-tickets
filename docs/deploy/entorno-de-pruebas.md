# Entorno de pruebas — qué, cómo y por qué

Documento del despliegue del sistema de tickets en un entorno de pruebas. Explica la tecnología
elegida, la arquitectura del despliegue, el runbook para levantarlo y el pipeline de CI/CD.

## TL;DR

- **Una VM única + Docker Compose**, con **Caddy** como edge (TLS automático) y un pipeline de
  GitHub Actions que **construye imágenes, las publica en GHCR y las despliega por SSH**.
- Artefactos: `docker-compose.prod.yml` (autocontenido), `docker/Dockerfile.{api,api-nginx,web}.prod`,
  `deploy/Caddyfile`, `.env.prod.example`, `.github/workflows/deploy.yml`.

## Por qué esta tecnología

El stack es pesado y heterogéneo: PHP-FPM + nginx, un worker, un relay de outbox, una SPA, y cuatro
servicios de datos (**PostgreSQL, Redis, RabbitMQ, Elasticsearch**). La decisión se tomó comparando:

| Opción | Veredicto |
|---|---|
| **VM única + Docker Compose** ✅ | Reutiliza fielmente el `docker-compose.yml` de desarrollo; todo el stack junto sin reescribir infra; demuestra provisioning + reverse proxy con TLS + CI/CD. **Elegida.** |
| App + servicios gestionados (RDS/Upstash/CloudAMQP/Bonsai) | Más "producción real", pero obliga a coordinar varios trials y DSNs para una prueba. Sobrecoste. |
| Fly.io (máquinas + volúmenes) | Buen free tier, pero Elasticsearch y RabbitMQ son quisquillosos de operar ahí. |
| Kubernetes (k3s/EKS) | Máximo lucimiento, pero el esfuerzo de manifiestos/Ingress/secrets no se justifica a esta escala. |

**Provider sugerido:** Oracle Cloud *Always Free* (ARM Ampere, hasta 24 GB de RAM — holgado para
Elasticsearch), o Hetzner Cloud (CX22), o el crédito de $200 de DigitalOcean. Cualquiera con Docker.

## Arquitectura del despliegue

```
                Internet (443/80)
                       │
                 ┌─────▼─────┐   TLS automático (Let's Encrypt)
                 │   Caddy   │   enruta por path
                 └──┬─────┬──┘
            /api/*  │     │  /*
          ┌─────────▼─┐ ┌─▼──────────┐
          │ api-nginx │ │ web (nginx │  SPA estática (build de Vite)
          │  (FastCGI)│ │  estático) │
          └─────┬─────┘ └────────────┘
          ┌─────▼─────┐
          │    api    │  PHP 8.4-FPM (imagen horneada, OPcache)
          └─────┬─────┘
   ┌────────────┼───────────────┬───────────────┐
┌──▼──┐   ┌─────▼─────┐   ┌──────▼──────┐  ┌──────▼──────┐
│ PG  │   │   Redis   │   │  RabbitMQ   │  │Elasticsearch│   (internos, sin puertos publicados)
└──▲──┘   └───────────┘   └──────▲──────┘  └──────▲──────┘
   │                             │                │
   │   ┌──────────────┐   ┌──────┴──────┐  ┌──────┴──────┐
   └───┤ outbox-relay ├──►│   worker    ├─►│  (indexa)   │
       │ (BD→Rabbit)  │   │(Rabbit→ES)  │  └─────────────┘
       └──────────────┘   └─────────────┘
```

**Decisiones y por qué:**

- **Único punto expuesto = Caddy.** PostgreSQL, Redis, RabbitMQ y Elasticsearch **no publican
  puertos**: sólo son accesibles dentro de la red Docker. Reduce superficie de ataque.
- **Caddy en vez de nginx+certbot** para el edge: HTTPS automático (emite y renueva el certificado
  solo) con tres líneas de config. Menos piezas que mantener.
- **Imágenes horneadas** (no se monta el código como en dev): artefacto inmutable y reproducible.
  `Dockerfile.api.prod` es multi-stage (vendor `--no-dev` cacheado por `composer.lock` + runtime con
  OPcache y `validate_timestamps=0`). La SPA se construye con Vite y se sirve estática por nginx.
- **api-nginx hornea `public/`** en la misma ruta absoluta que php-fpm (`/var/www/api/public`), de
  modo que el `SCRIPT_FILENAME` de FastCGI resuelve igual en ambos sin compartir volumen.
- **Migraciones como one-shot** (`api-migrate`): corre `doctrine:migrations:migrate` y la API sólo
  arranca cuando termina con éxito (`depends_on: service_completed_successfully`). Evita que la app
  sirva contra un esquema desactualizado.
- **worker y outbox-relay** son procesos aparte con `--time-limit` (reciclado anti memory-leak),
  igual que en dev. El relay materializa el outbox transaccional del [ADR 0008](../architecture/adr/0008-outbox-transaccional-ticketing.md).
- **Secretos fuera de la imagen:** `.env.prod` y las claves JWT viven en la VM (`deploy/secrets/jwt`,
  montado read-only), nunca en git ni en la imagen.
- **Compose autocontenido** (no override de `docker-compose.yml`): un override concatenaría la lista
  `volumes`, re-montando el código de dev sobre el horneado. El archivo de prod es independiente.

## Runbook (levantar el entorno)

Pre-requisitos: una VM con Docker + Docker Compose, un dominio apuntando a su IP (o usar
`<IP>.nip.io`), y los puertos 80/443 abiertos.

```bash
# 1. En la VM: clonar el repo
sudo mkdir -p /opt/tickets && sudo chown "$USER" /opt/tickets
git clone git@github.com:haefrain/gestion-de-tickets.git /opt/tickets
cd /opt/tickets

# 2. Configurar el entorno
cp .env.prod.example .env.prod
# editar .env.prod: DEPLOY_DOMAIN, ACME_EMAIL, APP_SECRET (openssl rand -hex 32),
# contraseñas de PostgreSQL/RabbitMQ y los DSN correspondientes, MAILER_DSN.

# 3. Generar el par de claves JWT (RS256) en el host, no en la imagen
mkdir -p deploy/secrets/jwt
docker run --rm -v "$PWD/deploy/secrets/jwt:/keys" -w /keys dunglas/frankenphp:latest sh -c '\
  openssl genpkey -out private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:"$JWT_PASSPHRASE" && \
  openssl pkey -in private.pem -passin pass:"$JWT_PASSPHRASE" -out public.pem -pubout'
# (o, más simple, dentro de un contenedor api ya construido:
#  docker compose -f docker-compose.prod.yml run --rm api php bin/console lexik:jwt:generate-keypair)
# La JWT_PASSPHRASE debe coincidir con la de .env.prod.

# 4. Construir/traer las imágenes y levantar
docker compose --env-file .env.prod -f docker-compose.prod.yml build      # o `pull` si usas GHCR
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d

# 5. (Opcional) Datos de demo
docker compose --env-file .env.prod -f docker-compose.prod.yml exec api php bin/console app:seed
```

Verificación: `https://<DEPLOY_DOMAIN>/api/v1/health` responde 200, la SPA carga en `/`, y
`docker compose -f docker-compose.prod.yml ps` muestra todo `healthy`. Un POST a `/api/v1/tickets`
deja una fila en `ticketing_outbox`; el `outbox-relay` la publica y el `worker` la indexa en ES.

## CI/CD — `deploy.yml`

Disparo: manual (`workflow_dispatch`, con input `tag`) o al publicar un tag `vX.Y.Z`.

1. **build-and-push** (matriz `api` · `api-nginx` · `web`): construye con `build-push-action`
   (cache de capas en GHA) y publica en **GHCR** (`ghcr.io/haefrain/gestion-de-tickets/<imagen>`).
2. **deploy**: SSH a la VM, `git pull`, `docker login ghcr.io`, `docker compose pull` + `up -d`.
   Las migraciones corren solas (el one-shot `api-migrate`). Entradas pasadas por `envs` (no
   interpoladas en el script) para evitar inyección.

**Secretos de GitHub necesarios** (Settings → Secrets, environment `staging`): `DEPLOY_HOST`,
`DEPLOY_USER`, `DEPLOY_SSH_KEY`. `GITHUB_TOKEN` lo provee Actions (con `packages: write`).

## Operación

- **Healthchecks** en todos los servicios; Caddy sólo enruta cuando `api-nginx` y `web` están sanos.
- **Rollback:** desplegar un tag anterior (`workflow_dispatch` con `tag=vX.Y.(Z-1)`), o en la VM
  `IMAGE_TAG=<anterior> docker compose --env-file .env.prod -f docker-compose.prod.yml up -d`.
  Las migraciones son aditivas; un rollback de imagen no revierte el esquema (política habitual).
- **Logs:** `docker compose -f docker-compose.prod.yml logs -f <servicio>`. Errores de aplicación
  van además a GlitchTip (si se añade el servicio, omitido aquí para aligerar el entorno de pruebas).
- **Backups:** los datos viven en volúmenes nombrados (`postgres_data`, etc.); `pg_dump` periódico
  recomendado para PostgreSQL.

## Qué se validó en local

- `docker compose --env-file .env.prod -f docker-compose.prod.yml config` válido (11 servicios).
- Las tres imágenes de producción **construyen** correctamente (composer `--no-dev` con autoloader
  autoritativo; build de la SPA con `tsc + vite`).
- El alta de la VM cloud (provisioning, DNS, claves) requiere credenciales reales y se ejecuta con
  este runbook.
