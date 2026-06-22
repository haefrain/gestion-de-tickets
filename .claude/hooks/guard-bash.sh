#!/usr/bin/env bash
# PreToolUse (Bash): guardarraíl. Bloquea (exit 2) comandos destructivos antes de ejecutarse.
# Cubre: rm recursivo-forzado, DROP/TRUNCATE de BD, git push --force/-f, mkfs, escritura a /dev.
# `git push --force-with-lease` se permite (es seguro).
set -uo pipefail

payload="$(cat)"

cmd="$(printf '%s' "$payload" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("tool_input",{}).get("command",""))' 2>/dev/null)"
if [ -z "${cmd:-}" ]; then
  cmd="$(printf '%s' "$payload" | sed -n 's/.*"command"[[:space:]]*:[[:space:]]*"\(.*\)"[[:space:]]*}.*/\1/p' | head -1)"
fi
[ -z "${cmd:-}" ] && exit 0

if printf '%s\n' "$cmd" | grep -qiE \
  'rm[[:space:]]+-[a-z]*r[a-z]*f|rm[[:space:]]+-[a-z]*f[a-z]*r|drop[[:space:]]+(database|schema|table)|truncate[[:space:]]+table|git[[:space:]]+push.*(--force([[:space:]]|$)|[[:space:]]-f([[:space:]]|$))|mkfs|>[[:space:]]*/dev/'; then
  {
    echo "⛔ Guardarraíl: comando potencialmente destructivo bloqueado."
    echo "   $cmd"
    echo "   Si es intencional, ejecútalo tú mismo fuera de Claude Code."
  } >&2
  exit 2
fi

exit 0
