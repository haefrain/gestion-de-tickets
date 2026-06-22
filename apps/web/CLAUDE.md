# Frontend (React) — Reglas · apps/web

App de apoyo que consume la API con JWT. Ver raíz `../../CLAUDE.md` y `../../docs/design-system/`.

## Stack

- React 19 · TypeScript (`strict`) · Vite · **MUI** · **TanStack Query** · React Router.

## Estructura

- `app/` — router y providers (Theme, Auth, QueryClient).
- `shared/` — `theme/` (tokens claro/oscuro índigo), `api/` (apiClient + JWT), `ui/` (design system).
- `features/` — `auth/`, `tickets/` (vistas, hooks, queries).

## Reglas

- Datos de servidor con **TanStack Query** (no `fetch` suelto en componentes).
- Estilos **solo** con tokens del tema MUI; nada hardcodeado.
- **JWT:** access token en memoria, refresh en cookie `HttpOnly`; rutas privadas con `RequireAuth`.
- Accesibilidad **WCAG 2.1 AA**.
- Cada componente del design system con su `*.stories.tsx` (ver `../../docs/design-system/storybook-spec.md`).

## Comandos

- `npm install` · `npm run dev` · `npm run lint` · `npm run test` (Vitest) · `npm run storybook`.

## Calidad

- TS strict + ESLint type-checked + Prettier; tests con Vitest + Testing Library; E2E con Playwright.
