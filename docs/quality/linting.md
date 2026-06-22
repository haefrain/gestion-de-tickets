# Linting y Análisis Estático

**Prueba Técnica · IATSAE** · Fase **F7 · Calidad & DevEx**

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.1 |
| **Fecha** | 2026-06-22 |
| **Política** | Rigor **estricto desde el día 1**; toda violación detiene el build |

## 1. Backend (PHP / Symfony)

| Herramienta | Configuración | Bloqueante |
|---|---|---|
| **PHPStan** | Nivel **9** (máx) + `phpstan-strict-rules` + extensión Symfony/Doctrine | Sí |
| **PHP-CS-Fixer** (o ECS) | PSR-12 + conjunto de reglas Symfony | Sí (`--dry-run` en CI) |
| **Rector** | Sets PHP 8.4 + Symfony, en modo *check* | Sí |
| **Composer audit** | Vulnerabilidades de dependencias | Sí |

> El nivel 9 obliga a tipado completo y elimina `mixed` implícitos; encaja con el dominio puro.

## 2. Frontend (TypeScript / React)

| Herramienta | Configuración | Bloqueante |
|---|---|---|
| **TypeScript** | `strict: true` + `noUncheckedIndexedAccess` + `noImplicitOverride` | Sí (`tsc --noEmit`) |
| **ESLint** | `typescript-eslint` *strict-type-checked* + reglas React/hooks/jsx-a11y | Sí |
| **Prettier** | Formato compartido, verificado en CI | Sí (`--check`) |
| **npm audit** | Vulnerabilidades de dependencias | Sí |

## 3. Commits y pre-commit

| Herramienta | Rol |
|---|---|
| **Commitlint** | Conventional Commits obligatorio |
| **lint-staged** (front) | ESLint + Prettier solo sobre archivos en *stage* |
| **captainhook / GrumPHP** (PHP) | PHPStan + CS-Fixer antes del commit |

Los hooks locales dan *feedback* rápido; CI es la red de seguridad final (misma configuración).

## 4. Filosofía

- **Cero warnings:** un warning es un error; no se acumula deuda.
- **Configuración versionada:** todos los configs viven en el repo y son idénticos local/CI.
- **Escalado:** se arranca en el nivel máximo; si bloquea el avance puntualmente, se usa *baseline* explícito y trazable, nunca bajar el nivel global.

## 5. Pendiente de iterar

- Decidir entre PHP-CS-Fixer y ECS (ambos válidos).
- Confirmar si activamos `exactOptionalPropertyTypes` (muy estricto) en TS.
- Definir baseline inicial de PHPStan si el arranque lo requiere.
