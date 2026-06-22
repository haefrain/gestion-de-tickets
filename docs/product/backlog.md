# Backlog de Producto — Sistema de Gestión de Tickets (MVP)

**Prueba Técnica · IATSAE** · Fase **F1 · Producto & Ágil**

> Este documento es el **índice** del backlog. Las HU detalladas (ficha técnica completa) viven en [`legends/`](legends/), un archivo por legenda. Está estructurado para que **Claude Code** lo lea y cree los **GitHub Issues** con su jerarquía (sub-issues) y labels.

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.2 (HU enriquecidas) |
| **Fecha** | 2026-06-22 |
| **Alcance** | MVP "stack completo" (JWT · Tickets · Elasticsearch · RabbitMQ · Redis · React) |
| **Roles** | Cliente · Agente · Admin |
| **Jerarquía** | Legenda → Épica → Historia de Usuario (HU) |
| **Total** | 7 legendas · 17 épicas · 38 HU |

---

## 1. Convención de mapeo a GitHub Issues

| Concepto | En GitHub |
|---|---|
| **Legenda** (iniciativa) | Issue raíz con label `type:legend` |
| **Épica** | Sub-issue de la legenda, label `type:epic` |
| **HU** | Sub-issue de la épica, label `type:story` |
| **Tipo** | Label `type:*` (universal). Si el repo está en una organización con *Issue Types*, usar además el type nativo |
| **Prioridad** | Label `priority:must` · `priority:should` · `priority:could` (MoSCoW) |
| **Área** | Label `area:*` (ver §2) |
| **Estado** | Columna del **Project** (Todo / In progress / In review / Done), no label |
| **Release** | **Milestone** `MVP` |
| **Dependencias** | *Issue dependencies* nativas (campo "Depende de" en cada HU) |
| **Tablero** | **Project** "Tickets MVP" con vista jerárquica |

**Cómo lo materializa Claude Code** (gh CLI ≥ 2.94, que ya soporta sub-issues, types y dependencies):

1. Crear el **milestone** `MVP` y el **Project** "Tickets MVP".
2. Crear las **legendas** como issues raíz (label `type:legend`).
3. Por cada legenda, crear sus **épicas** y enlazarlas como **sub-issues**.
4. Por cada épica, crear sus **HU** como sub-issues, con el cuerpo de la ficha (criterios, contrato, diseño, tests), labels, estimación y dependencias.
5. Añadir todos los issues al Project y asignarlos al milestone `MVP`.
6. Confirmar flags exactos con `gh issue create --help` antes de ejecutar en lote.

## 2. Etiquetas (labels)

| Label | Uso |
|---|---|
| `type:legend` `type:epic` `type:story` `type:bug` | Nivel jerárquico |
| `priority:must` `priority:should` `priority:could` | Prioridad MoSCoW |
| `area:auth` `area:tickets` `area:search` `area:async` `area:cache` `area:frontend` `area:platform` | Dominio/capa |

## 3. Definition of Ready / Definition of Done

**DoR (lista para desarrollar):** la HU tiene rol + objetivo + beneficio, criterios de aceptación claros, estimación, sin bloqueos abiertos y dependencias identificadas.

**DoD (terminada):** criterios de aceptación cumplidos; tests (unit/integración/E2E) en verde; cobertura ≥ 80% en dominio/aplicación; linters sin violaciones; código revisado por PR; documentada; ejecutable vía `docker-compose`.

## 4. Estimación y prioridad

- **Estimación:** Story Points en Fibonacci (`1, 2, 3, 5, 8`). Un `13` debe dividirse.
- **MoSCoW:** `Must` = núcleo del MVP · `Should` = importante · `Could` = deseable si hay margen.

## 5. Resumen de legendas

| ID | Legenda | Épicas | HU | Foco técnico |
|---|---|---|---|---|
| **L1** | Identidad y Acceso | 3 | 8 | JWT · RBAC |
| **L2** | Gestión de Tickets | 3 | 10 | Dominio (hexagonal) · PostgreSQL |
| **L3** | Búsqueda y Descubrimiento | 2 | 4 | Elasticsearch |
| **L4** | Notificaciones y Asincronía | 2 | 4 | RabbitMQ · Messenger |
| **L5** | Rendimiento y Cache | 2 | 3 | Redis |
| **L6** | Experiencia de Usuario (Front) | 2 | 4 | React · MUI · JWT |
| **L7** | Plataforma, Calidad y DevEx | 3 | 5 | Docker · CI · Claude Code |

## 6. Índice del backlog detallado

Cada archivo contiene las HU de su legenda con **ficha técnica completa** (narrativa, criterios Gherkin, contrato API, diseño CQRS/eventos/dominio, infraestructura, seguridad, tests y DoD).

| Legenda | Archivo | HU |
|---|---|---|
| **L1** · Identidad y Acceso | [`legends/L1-identidad.md`](legends/L1-identidad.md) | 8 |
| **L2** · Gestión de Tickets | [`legends/L2-tickets.md`](legends/L2-tickets.md) | 10 |
| **L3** · Búsqueda y Descubrimiento | [`legends/L3-busqueda.md`](legends/L3-busqueda.md) | 4 |
| **L4** · Notificaciones y Asincronía | [`legends/L4-notificaciones.md`](legends/L4-notificaciones.md) | 4 |
| **L5** · Rendimiento y Cache | [`legends/L5-cache.md`](legends/L5-cache.md) | 3 |
| **L6** · Experiencia de Usuario | [`legends/L6-frontend.md`](legends/L6-frontend.md) | 4 |
| **L7** · Plataforma, Calidad y DevEx | [`legends/L7-plataforma.md`](legends/L7-plataforma.md) | 5 |

> El formato de ficha se validó en [`ejemplo-ficha-hu.md`](ejemplo-ficha-hu.md).

## 7. Decisiones cerradas y pendientes

**Cerradas (2026-06-22):**

- ✅ **Primer corte del MVP:** solo las HU `Must` (**23 HU**). Las `Should`/`Could` quedan para una segunda iteración.
- ✅ **Notificaciones por email:** **SMTP real** vía Symfony Mailer, con DSN configurable en `.env` (en local puede apuntarse a un sandbox tipo Mailtrap). Se implementan en las HU de notificaciones (L4-E2, `Should`) → segunda iteración.

> ⚠️ **Nota:** como las notificaciones por email son `Should`, no entran en el primer corte (solo `Must`). El procesamiento asíncrono `Must` (L4-E1) se ejercita igualmente con el **indexado** en Elasticsearch.

**Pendiente:**

- Ajustar criterios de aceptación si surgen casos límite durante la implementación.
