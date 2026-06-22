# DevEx con IA — Configuración de Claude Code

**Prueba Técnica · IATSAE** · Fase **F8 · DevEx (transversal)**

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.1 |
| **Fecha** | 2026-06-22 |
| **Permisos** | Amplio (auto-aprobar salvo destructivos) |
| **Entorno** | Solo local (no productivo) |

> Toda la configuración de Claude Code se versiona en el repo para que el equipo desarrolle de forma consistente. Este documento **define** qué configuramos; los archivos `.claude/` se materializan en la fase de implementación.

## 1. Estructura de archivos

```text
prueba-tecnica-iatsae/
├── CLAUDE.md                      # Rules raíz del proyecto
├── apps/api/CLAUDE.md             # Rules del backend (hexagonal)
├── apps/web/CLAUDE.md             # Rules del frontend (MUI/React)
├── .mcp.json                      # MCP servers del equipo
└── .claude/
    ├── agents/                    # Subagentes
    ├── skills/                    # Skills de scaffolding
    ├── settings.json              # Permisos + hooks (commiteado)
    └── settings.local.json        # Overrides personales (gitignored)
```

## 2. Rules (CLAUDE.md)

| Ámbito | Reglas clave |
|---|---|
| **Raíz** | Estructura del monorepo, comandos `make`, Conventional Commits, "el dominio no conoce framework" |
| **Backend** | Capas Domain/Application/Infrastructure, puertos y adaptadores, UUID v7, CQRS, PHPStan 9 |
| **Frontend** | MUI + tokens del tema, TanStack Query para datos, accesibilidad AA, TS strict |

## 3. Subagentes

| Subagente | Responsabilidad | Se invoca para… |
|---|---|---|
| **Revisor de arquitectura** | Verifica hexagonal/SOLID y límites de contexto | Revisar PRs y cambios estructurales |
| **Revisor de seguridad** | JWT/RBAC, validación de entrada, dependencias | Cambios en auth o endpoints sensibles |
| **Tests** | Genera y ejecuta tests, reporta cobertura | Añadir o verificar cobertura de un caso de uso |
| **Documentación/ADRs** | Mantiene docs y redacta ADRs | Registrar decisiones de arquitectura |

Cada subagente vive en `.claude/agents/<nombre>.md` con frontmatter (`name`, `description`, `tools`, `model`).

## 4. Skills de scaffolding

| Skill | Genera |
|---|---|
| **Crear caso de uso hexagonal** | Command + Handler + puerto + adaptador con la estructura del contexto |
| **Crear endpoint + contrato** | Controller + DTO + ruta + esqueleto de test |
| **Nueva HU → issue** | Historia de usuario e issue de GitHub desde la plantilla del backlog |
| **Generar ADR** | ADR con el formato de `docs/architecture/adr/` |

Cada skill vive en `.claude/skills/<nombre>/SKILL.md`.

## 5. Hooks

| Hook | Evento | Acción |
|---|---|---|
| Formateo automático | `PostToolUse` (Write/Edit) | PHP-CS-Fixer (PHP) y Prettier (TS) sobre el archivo tocado |
| Análisis incremental | `PostToolUse` (PHP) | PHPStan sobre el archivo modificado |
| Guardarraíl | `PreToolUse` (Bash) | Bloquear comandos destructivos (`rm -rf`, `DROP`, `git push --force`) |

## 6. MCP servers (.mcp.json)

| Server | Uso |
|---|---|
| **GitHub** | Gestionar issues y PRs (materializar el backlog, revisar) |
| **PostgreSQL** | Inspeccionar esquema y consultar la BD de desarrollo |
| **Elasticsearch** | Inspeccionar índices, mappings y consultas |
| **GlitchTip** (observabilidad) | Consultar errores capturados localmente |

Las credenciales van en `settings.local.json` (gitignored); `.mcp.json` solo define los servers.

## 7. Manejador de errores local (Docker)

Como el proyecto es solo local, se incluye **GlitchTip** —error tracker open-source, compatible con el SDK de Sentry— como servicio en `docker-compose`.

- **Backend:** `sentry/sentry-symfony` apuntando al DSN de GlitchTip local.
- **Frontend:** `@sentry/react` apuntando al mismo DSN.
- **Resultado:** panel local donde se ven los errores de la API y de la SPA, sin servicios SaaS.
- *Alternativa considerada:* Sentry self-hosted (más pesado); GlitchTip es más ligero para Docker local.

## 8. Permisos (modelo amplio)

Al ser un entorno **solo local y no productivo**, se opta por autonomía amplia para agilizar.

| Regla | Ejemplos |
|---|---|
| **Allow** (auto) | `make *`, `composer *`, `npm *`, `git` (lectura/commit), tests, linters, edición de archivos |
| **Deny** (siempre) | `rm -rf`, `DROP DATABASE`, `git push --force`, exfiltración de secretos |

`settings.json` (commiteado) fija el modelo compartido; `settings.local.json` (gitignored) permite ajustes personales. La memoria automática y los settings locales van al `.gitignore`.

## 9. Pendiente de iterar

- Confirmar versión de GlitchTip y su `docker-compose`.
- Afinar la lista exacta de comandos en allow/deny.
- Decidir el modelo por defecto de los subagentes.
- Materializar los archivos `.claude/` en la fase de implementación.
