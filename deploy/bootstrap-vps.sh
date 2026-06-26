#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────────────────────
# Bootstrap de un VPS x86 (Ubuntu/Debian) para el entorno de pruebas del sistema
# de tickets. Deja una URL https funcionando con un solo comando: instala Docker,
# prepara el host (swap, vm.max_map_count, firewall), genera secretos y claves
# JWT, y construye + levanta el stack de producción (docker-compose.prod.yml).
#
# Uso (en la VM, con un usuario con sudo):
#   # opción A — directo desde GitHub:
#   curl -fsSL https://raw.githubusercontent.com/haefrain/gestion-de-tickets/main/deploy/bootstrap-vps.sh \
#     | bash -s -- --domain tickets.midominio.dev --email yo@correo.com
#
#   # opción B — si ya clonaste el repo:
#   ./deploy/bootstrap-vps.sh --domain tickets.midominio.dev --email yo@correo.com
#
# Sin --domain usa <IP-pública>.nip.io (TLS de Let's Encrypt; ojo con los rate
# limits de nip.io: si la emisión del certificado falla, usá un dominio propio).
#
# Flags:
#   --domain <dom>   Dominio público para el certificado (o IP.nip.io).
#   --email <mail>   Email de avisos de Let's Encrypt.
#   --mailer <dsn>   MAILER_DSN (por defecto null://null = no envía correos).
#   --dir <ruta>     Directorio destino del repo (por defecto /opt/tickets).
#   --repo <url>     URL del repositorio a clonar.
#   --no-seed        No cargar los datos de demo.
# ──────────────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/haefrain/gestion-de-tickets.git}"
TARGET_DIR="${TARGET_DIR:-/opt/tickets}"
DOMAIN=""
EMAIL=""
MAILER_DSN="null://null"
SEED=1

while [ $# -gt 0 ]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2 ;;
    --email)  EMAIL="$2";  shift 2 ;;
    --mailer) MAILER_DSN="$2"; shift 2 ;;
    --dir)    TARGET_DIR="$2"; shift 2 ;;
    --repo)   REPO_URL="$2"; shift 2 ;;
    --no-seed) SEED=0; shift ;;
    *) echo "Argumento desconocido: $1" >&2; exit 1 ;;
  esac
done

log()  { printf '\n\033[1;36m▶ %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m  ! %s\033[0m\n' "$*"; }

SUDO=""
[ "$(id -u)" -ne 0 ] && SUDO="sudo"

# ── 1. Docker + plugin compose ────────────────────────────────────────────────
if ! command -v docker >/dev/null 2>&1; then
  log "Instalando Docker…"
  curl -fsSL https://get.docker.com | $SUDO sh
  [ -n "${SUDO}" ] && $SUDO usermod -aG docker "$USER" || true
fi
DC="$SUDO docker compose --env-file .env.prod -f docker-compose.prod.yml"

# ── 2. Preparación del host ───────────────────────────────────────────────────
# Elasticsearch 8.x exige vm.max_map_count alto; en un VPS suele venir bajo.
if [ "$(cat /proc/sys/vm/max_map_count 2>/dev/null || echo 0)" -lt 262144 ]; then
  log "Ajustando vm.max_map_count (requerido por Elasticsearch)…"
  $SUDO sysctl -w vm.max_map_count=262144
  echo 'vm.max_map_count=262144' | $SUDO tee /etc/sysctl.d/99-elasticsearch.conf >/dev/null
fi

# Swap de 2 GB si el VPS no tiene (evita OOM en máquinas de 4 GB con ES + todo).
if [ "$(free -m | awk '/^Swap:/{print $2}')" = "0" ]; then
  log "Creando 2 GB de swap…"
  $SUDO fallocate -l 2G /swapfile && $SUDO chmod 600 /swapfile
  $SUDO mkswap /swapfile && $SUDO swapon /swapfile
  echo '/swapfile none swap sw 0 0' | $SUDO tee -a /etc/fstab >/dev/null
fi

# Firewall del host (si ufw está activo). El firewall del proveedor —si lo
# activaste en su panel— hay que abrirlo aparte (puertos 80 y 443).
if command -v ufw >/dev/null 2>&1 && $SUDO ufw status 2>/dev/null | grep -q "Status: active"; then
  log "Abriendo 80/443 en ufw…"
  $SUDO ufw allow 80/tcp  || true
  $SUDO ufw allow 443/tcp || true
fi

# ── 3. Localizar / clonar el repo ─────────────────────────────────────────────
if [ -f docker-compose.prod.yml ]; then
  TARGET_DIR="$PWD"   # ya estamos dentro del repo: usarlo tal cual, no clonar otra copia
elif [ ! -d "$TARGET_DIR/.git" ]; then
  log "Clonando el repositorio en $TARGET_DIR…"
  $SUDO mkdir -p "$TARGET_DIR"
  $SUDO chown "$(id -un):$(id -gn)" "$TARGET_DIR"
  git clone "$REPO_URL" "$TARGET_DIR"
fi
cd "$TARGET_DIR"

# ── 4. Dominio ────────────────────────────────────────────────────────────────
if [ -z "$DOMAIN" ]; then
  IP="$(curl -fsSL https://api.ipify.org || curl -fsSL https://ifconfig.me)"
  DOMAIN="${IP}.nip.io"
  warn "Sin --domain: uso $DOMAIN. Si Let's Encrypt falla por rate limit, pasá un dominio propio."
fi
[ -z "$EMAIL" ] && EMAIL="admin@${DOMAIN}"

# ── 5. .env.prod (no se pisa si ya existe: conserva los secretos) ─────────────
if [ ! -f .env.prod ]; then
  log "Generando .env.prod con secretos aleatorios…"
  APP_SECRET="$(openssl rand -hex 32)"
  PG_PASS="$(openssl rand -hex 24)"
  MQ_PASS="$(openssl rand -hex 24)"
  JWT_PASS="$(openssl rand -hex 24)"
  cat > .env.prod <<EOF
DEPLOY_DOMAIN=$DOMAIN
ACME_EMAIL=$EMAIL
APP_ENV=prod
DEFAULT_URI=https://$DOMAIN
APP_SECRET=$APP_SECRET
POSTGRES_USER=tickets
POSTGRES_PASSWORD=$PG_PASS
POSTGRES_DB=tickets
DATABASE_URL=postgresql://tickets:$PG_PASS@postgres:5432/tickets?serverVersion=16&charset=utf8
REDIS_URL=redis://redis:6379
RABBITMQ_DEFAULT_USER=tickets
RABBITMQ_DEFAULT_PASS=$MQ_PASS
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=tickets
RABBITMQ_PASSWORD=$MQ_PASS
RABBITMQ_VHOST=/
MESSENGER_TRANSPORT_DSN=amqp://tickets:$MQ_PASS@rabbitmq:5672/%2f/messages
ELASTICSEARCH_URL=http://elasticsearch:9200
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=$JWT_PASS
MAILER_DSN=$MAILER_DSN
IMAGE_TAG=latest
GHCR_NAMESPACE=tickets-iatsae
EOF
  # El .env.prod se monta como /var/www/api/.env en los contenedores PHP (corren como www-data, uid 33).
  # Legible solo por ese uid (640 + dueño www-data), no por todo el sistema. Fallback a 644 si no hay root.
  chown 33:33 .env.prod 2>/dev/null && chmod 640 .env.prod || chmod 644 .env.prod
else
  warn ".env.prod ya existe — lo conservo (no regenero secretos)."
fi

# ── 6. Claves JWT RS256 (en el host, montadas read-only; no se hornean) ───────
if [ ! -f deploy/secrets/jwt/private.pem ]; then
  log "Generando el par de claves JWT RS256…"
  JWT_PASS="$(grep '^JWT_PASSPHRASE=' .env.prod | cut -d= -f2-)"
  mkdir -p deploy/secrets/jwt
  openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 \
    -aes256 -pass "pass:$JWT_PASS" -out deploy/secrets/jwt/private.pem 2>/dev/null
  openssl pkey -in deploy/secrets/jwt/private.pem -passin "pass:$JWT_PASS" \
    -pubout -out deploy/secrets/jwt/public.pem 2>/dev/null
  # 644: el contenedor api corre como www-data y debe poder leerlas (entorno de pruebas).
  chmod 644 deploy/secrets/jwt/private.pem deploy/secrets/jwt/public.pem
fi

# ── 7. Construir + levantar ───────────────────────────────────────────────────
log "Construyendo imágenes y levantando el stack (esto tarda la primera vez)…"
$DC up -d --build --remove-orphans

# ── 8. Datos de demo (opcional) ───────────────────────────────────────────────
if [ "$SEED" = 1 ]; then
  log "Esperando a que la API esté lista para el seed…"
  for _ in $(seq 1 40); do
    if $DC exec -T api php bin/console --version >/dev/null 2>&1; then break; fi
    sleep 3
  done
  $DC exec -T api php bin/console app:seed || warn "El seed se puede correr luego: $DC exec api php bin/console app:seed"
fi

log "Listo ✅"
echo "   SPA:    https://$DOMAIN/"
echo "   API:    https://$DOMAIN/api/v1/health"
echo "   Demo:   cliente@demo.local / agente@demo.local / admin@demo.local  ·  contraseña Demo1234"
echo
echo "   Estado: $DC ps        ·  Logs: $DC logs -f <servicio>"
