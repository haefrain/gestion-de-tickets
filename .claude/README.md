# Configuración de Claude Code (`.claude/`)

Configuración versionada para que el equipo desarrolle de forma consistente. Materializa `docs/ai-devex/overview.md` (Fase F1).

## Archivos

| Ruta | Versionado | Propósito |
|---|---|---|
| `settings.json` | ✅ commiteado | Permisos (allow amplio / deny destructivos) + hooks del equipo |
| `settings.local.json` | ❌ gitignored | Overrides personales y credenciales (MCP) |
| `settings.local.example.json` | ✅ commiteado | Plantilla de los overrides locales |
| `agents/` | ✅ | Subagentes (revisores, tests, docs) |
| `skills/` | ✅ | Skills de scaffolding |
| `hooks/` | ✅ | Scripts de los hooks |
| `../.mcp.json` | ✅ | Servidores MCP del proyecto |

## Hooks (`hooks/`)

| Hook | Evento | Acción |
|---|---|---|
| `format.sh` | PostToolUse · Write/Edit | PHP-CS-Fixer (`.php`) y Prettier (`.ts/.tsx/...`) vía contenedores |
| `phpstan.sh` | PostToolUse · Write/Edit | PHPStan nivel 9 incremental sobre el `.php` tocado (informativo) |
| `guard-bash.sh` | PreToolUse · Bash | Bloquea comandos destructivos (`rm -rf`, `DROP`, `git push --force`, …) |

Las herramientas (CS-Fixer, Prettier, PHPStan) viven dentro de los contenedores. Hasta que F2/F3 ejecuten `composer install` / `npm install`, los hooks de formato/análisis se ejecutan en modo **tolerante** (no fallan; empiezan a actuar cuando las herramientas existen).

## Subagentes (`agents/`)

`revisor-arquitectura` · `revisor-seguridad` · `tests` · `docs-adr`.

## Skills (`skills/`)

`crear-caso-uso-hexagonal` · `crear-endpoint` · `hu-a-issue` · `generar-adr`.

## MCP (`../.mcp.json`)

`github` (Docker) · `postgres` (npx) · `elasticsearch` (npx `@elastic/mcp-server-elasticsearch`).

Los servidores de proyecto **requieren tu aprobación** al iniciar la sesión y credenciales en `settings.local.json` (copia `settings.local.example.json`). En esta máquina, Postgres se expone en el puerto `55432` (ver `.env`); en una limpia es `5432`.

> **GlitchTip:** no se incluye en `.mcp.json` porque no existe un servidor MCP estándar para GlitchTip (coincide con el "pendiente de iterar" de `docs/ai-devex/overview.md`). La observabilidad local se consulta por su panel web en `http://localhost:8000`.

## Activación

Los hooks, subagentes y skills se cargan al **iniciar** la sesión de Claude Code. Tras cambios en `.claude/`, reinicia la sesión (o revisa con `/hooks`) para que tomen efecto.
