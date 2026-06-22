---
name: hu-a-issue
description: Convierte una historia de usuario del backlog (docs/product/) en un issue de GitHub con la ficha técnica completa, labels y dependencias. Úsalo al materializar el backlog (F5) o al crear una HU nueva.
---

# Nueva HU → issue de GitHub

Crea un issue de GitHub a partir de una HU del backlog, respetando `docs/product/backlog.md` y la plantilla `docs/product/ejemplo-ficha-hu.md`.

## Entradas que necesitas

- **Identificador de la HU** (p. ej. `HU-L2-E1-01`) y su legenda/épica.
- Su ficha en `docs/product/legends/L*.md` (narrativa, criterios de aceptación, contrato de API, prioridad MoSCoW, estimación, dependencias).

## Procedimiento

1. **Lee la ficha** de la HU en `docs/product/`. No inventes criterios: cópialos de la fuente.
2. **Título:** `[HU-Lx-Ey-zz] <descripción en imperativo>`.
3. **Cuerpo (desde la plantilla):** narrativa («Como… quiero… para…»), criterios de aceptación verificables (checklist), contrato de API si aplica, diseño técnico, notas de tests y **DoD**.
4. **Labels:** prioridad (`must`/`should`/`could`), área (`area:auth`, `area:tickets`, …), plataforma (`platform:backend`/`platform:web`), tipo (`type:feature`/…).
5. **Jerarquía y dependencias:** enlaza la épica/legenda padre y declara `Bloquea`/`Bloqueado por` (p. ej. la auth de L1 bloquea el frontend de L6).
6. **Milestone/Project:** asigna a `MVP` y al project "Tickets MVP".

## Comando base

```bash
gh issue create --title "[HU-...] ..." --body-file <archivo> \
  --label "must,area:tickets,platform:backend,type:feature" --milestone "MVP"
```

## Verificación

`gh issue list` muestra la HU con su título, labels y jerarquía correctos.
