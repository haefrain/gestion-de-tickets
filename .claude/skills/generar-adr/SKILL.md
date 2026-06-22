---
name: generar-adr
description: Crea un Architecture Decision Record en docs/architecture/adr/ con el formato y la numeración de los ADR existentes. Úsalo al registrar una decisión de arquitectura.
---

# Generar ADR

Crea un ADR en `docs/architecture/adr/` siguiendo el formato de los existentes.

## Procedimiento

1. **Numeración:** lista `docs/architecture/adr/` y asigna el siguiente número secuencial (`000N`). Revisa `docs/architecture/adr/README.md`.
2. **Plantilla:** lee un ADR existente (p. ej. `0001-arquitectura-hexagonal.md`) y replica **exactamente** su estructura:
   - Título: `# N. <Decisión>`
   - **Estado:** Propuesta · Aceptada · Rechazada · Reemplazada por ADR-XXXX
   - **Contexto:** el problema/fuerzas en juego (el porqué).
   - **Decisión:** qué se decide, en presente afirmativo.
   - **Consecuencias:** positivas y negativas (trade-offs honestos).
   - **Alternativas consideradas:** opciones descartadas y por qué.
3. **Nombre de archivo:** `000N-titulo-en-kebab-case.md`.
4. **Una decisión por ADR.** Si reemplaza a otro, enlázalos y marca el anterior como *Reemplazada*.

## Reglas

- Escribe en español, conciso. Registra el **porqué**, no el cómo del código.
- No modifiques ADRs ya *Aceptados*; crea uno nuevo que los reemplace si la decisión cambia.

## Verificación

El nuevo archivo existe con número correcto, sigue el formato y queda enlazado desde el `README.md` del directorio si este indexa los ADR.
