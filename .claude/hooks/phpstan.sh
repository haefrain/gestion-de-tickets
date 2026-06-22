#!/usr/bin/env bash
# PostToolUse (Write|Edit|MultiEdit): análisis estático incremental con PHPStan (nivel 9)
# sobre el archivo .php tocado, vía el contenedor api. Informativo (exit 0): muestra los
# hallazgos en stderr sin bloquear la edición. Tolerante si PHPStan aún no está instalado.
set -uo pipefail

payload="$(cat)"

file="$(printf '%s' "$payload" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("tool_input",{}).get("file_path",""))' 2>/dev/null)"
if [ -z "${file:-}" ]; then
  file="$(printf '%s' "$payload" | sed -n 's/.*"file_path"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)"
fi
[ -z "${file:-}" ] && exit 0

proj="${CLAUDE_PROJECT_DIR:-$(pwd)}"

case "$file" in
  "$proj"/apps/api/*.php)
    rel="${file#"$proj"/apps/api/}"
    cd "$proj" 2>/dev/null || exit 0
    # Existencia con cadena fija; análisis con argv directo ($rel como argumento único).
    if docker compose exec -T api sh -c 'test -x vendor/bin/phpstan' >/dev/null 2>&1; then
      out="$(docker compose exec -T api vendor/bin/phpstan analyse --no-progress --level=9 --error-format=raw -- "$rel" 2>/dev/null)" || true
      if [ -n "${out:-}" ]; then
        printf 'PHPStan (nivel 9) — %s:\n%s\n' "$rel" "$out" >&2
      fi
    fi
    ;;
esac

exit 0
