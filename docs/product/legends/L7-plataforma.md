# L7 · Plataforma, Calidad y DevEx

**Backlog rico** · Transversal (enlaza F0, F7, F8) · [↩ índice](../backlog.md)

> Reproducibilidad en Docker, calidad automatizada y configuración de Claude Code. Incluye GlitchTip como manejador de errores local.

---

## Épica L7-E1 · Entorno reproducible

### HU-L7-E1-01 · docker-compose levanta todo

**Narrativa:** Como **desarrollador** quiero **un solo comando** para **ejecutar el proyecto en cualquier máquina**.

| Metadato | Valor |
|---|---|
| **Contexto** | Platform · **Épica:** L7-E1 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:platform`, `type:story` |
| **Depende de** | — |

**Criterios de aceptación**

```gherkin
Escenario: Arranque completo
  Dado un clon limpio del repo
  Cuando ejecuto "make up"
  Entonces se levantan API, Web, PostgreSQL, Redis, RabbitMQ, Elasticsearch y GlitchTip

Escenario: Documentación de arranque
  Dado el README
  Entonces describe el arranque y las variables .env necesarias
```

**Entregables técnicos:** `docker-compose.yml`, `Makefile` (`up`, `down`, `test`, `lint`), `.env.example`, `README.md`.

**Infraestructura:** todos los servicios como contenedores; **GlitchTip** (error tracking local, compatible con SDK de Sentry).

**Tests** · *Manual/CI:* `make up` deja el stack operativo; healthchecks en verde.

---

### HU-L7-E1-02 · Seeds y datos de demo

**Narrativa:** Como **evaluador** quiero **datos de ejemplo** para **probar el sistema sin configurarlo**.

| Metadato | Valor |
|---|---|
| **Contexto** | Platform · **Épica:** L7-E1 |
| **Prioridad** | Should · **Estimación:** 2 · **Labels:** `area:platform`, `type:story` |
| **Depende de** | HU-L7-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Datos de demo
  Cuando ejecuto el comando de seeds
  Entonces se crean usuarios de cada rol (Cliente, Agente, Admin) y tickets de ejemplo

Escenario: Recarga
  Cuando recargo el estado de demo
  Entonces los datos vuelven a un estado conocido
```

**Entregables técnicos:** fixtures/factories; comando `make seed`.

**Infraestructura:** **PostgreSQL** (datos), reindexado a **Elasticsearch**.

**Tests** · *Integración:* los seeds crean los roles y tickets esperados.

---

## Épica L7-E2 · Calidad automatizada

### HU-L7-E2-01 · Linters y análisis estático en CI

**Narrativa:** Como **equipo** quiero **linters bloqueantes** para **mantener la calidad**.

| Metadato | Valor |
|---|---|
| **Contexto** | Platform · **Épica:** L7-E2 |
| **Prioridad** | Must · **Estimación:** 3 · **Labels:** `area:platform`, `type:story` |
| **Depende de** | HU-L7-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Análisis en CI
  Dado un Pull Request
  Cuando corre el pipeline
  Entonces ejecuta PHPStan 9, CS-Fixer, ESLint y Prettier

Escenario: Violación bloqueante
  Dado un error de lint o de análisis
  Entonces el pipeline falla y bloquea el merge
```

**Entregables técnicos:** configs de PHPStan/ECS/ESLint/Prettier + workflow de **GitHub Actions** (ver [`quality/linting.md`](../../quality/linting.md)).

**Tests** · *CI:* un cambio que viola reglas falla el job.

---

### HU-L7-E2-02 · Suite de tests y pipeline verde

**Narrativa:** Como **equipo** quiero **tests automatizados** para **evitar regresiones**.

| Metadato | Valor |
|---|---|
| **Contexto** | Platform · **Épica:** L7-E2 |
| **Prioridad** | Must · **Estimación:** 5 · **Labels:** `area:platform`, `type:story` |
| **Depende de** | HU-L7-E2-01 |

**Criterios de aceptación**

```gherkin
Escenario: Tests en cada PR
  Dado un Pull Request
  Cuando corre el pipeline
  Entonces ejecuta unit + integración + E2E y exige verde para fusionar

Escenario: Cobertura mínima
  Dado el reporte de cobertura
  Entonces el dominio/aplicación alcanza al menos 80% (bloqueante)
```

**Entregables técnicos:** PHPUnit, Vitest + Testing Library, Playwright; gate de cobertura (ver [`quality/testing.md`](../../quality/testing.md)).

**Tests** · *CI:* la pipeline ejecuta las tres capas y publica cobertura.

---

## Épica L7-E3 · DevEx con IA (Claude Code)

### HU-L7-E3-01 · Configuración base de Claude Code

**Narrativa:** Como **equipo** quiero **reglas y automatizaciones de Claude Code** para **desarrollar de forma consistente**.

| Metadato | Valor |
|---|---|
| **Contexto** | Platform · **Épica:** L7-E3 |
| **Prioridad** | Should · **Estimación:** 3 · **Labels:** `area:platform`, `type:story` |
| **Depende de** | HU-L7-E1-01 |

**Criterios de aceptación**

```gherkin
Escenario: Configuración operativa
  Dado el repo
  Entonces existe CLAUDE.md con reglas y comandos
  Y al menos un subagente, una skill y un hook de lint operativos

Escenario: Permisos y gitignore
  Entonces los permisos están definidos
  Y settings.local.json y la memoria automática están en .gitignore
```

**Entregables técnicos:** `CLAUDE.md` (+ anidados), `.claude/agents`, `.claude/skills`, `.claude/settings.json`, `.mcp.json` (ver [`ai-devex/overview.md`](../../ai-devex/overview.md)).

**Tests** · *Manual:* el hook de formato corre al editar; los subagentes/skills se invocan.
