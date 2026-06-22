#!/usr/bin/env bash
# PostToolUse (Write|Edit|MultiEdit): formatea el archivo tocado vía contenedores Docker.
#  - *.php (apps/api)            → PHP-CS-Fixer
#  - *.ts/tsx/js/jsx/json/css (apps/web) → Prettier
# Tolerante: si el contenedor o la herramienta no están disponibles (p. ej. antes de
# `composer install` / `npm install` en F2/F3), no falla — simplemente no formatea.
set -uo pipefail

payload="$(cat)"

# Extrae tool_input.file_path (python3 → fallback sed)
file="$(printf '%s' "$payload" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("tool_input",{}).get("file_path",""))' 2>/dev/null)"
if [ -z "${file:-}" ]; then
  file="$(printf '%s' "$payload" | sed -n 's/.*"file_path"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)"
fi
[ -z "${file:-}" ] && exit 0

proj="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$proj" 2>/dev/null || exit 0

case "$file" in
  "$proj"/apps/api/*.php)
    rel="${file#"$proj"/apps/api/}"
    # Chequeo de existencia (cadena fija, sin datos no confiables) y luego ejecución
    # con argv directo: $rel viaja como un único argumento, nunca como shell string.
    if docker compose exec -T api sh -c 'test -x vendor/bin/php-cs-fixer' >/dev/null 2>&1; then
      docker compose exec -T api vendor/bin/php-cs-fixer fix --quiet -- "$rel" >/dev/null 2>&1 || true
    fi
    ;;
  "$proj"/apps/web/*.ts|"$proj"/apps/web/*.tsx|"$proj"/apps/web/*.js|"$proj"/apps/web/*.jsx|"$proj"/apps/web/*.json|"$proj"/apps/web/*.css)
    rel="${file#"$proj"/apps/web/}"
    if docker compose exec -T web sh -c 'test -x node_modules/.bin/prettier' >/dev/null 2>&1; then
      docker compose exec -T web node_modules/.bin/prettier --write -- "$rel" >/dev/null 2>&1 || true
    fi
    ;;
esac

exit 0
