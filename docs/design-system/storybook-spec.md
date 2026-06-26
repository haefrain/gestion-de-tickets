# Spec de Storybook — Galería de Componentes

**Prueba Técnica · IATSAE** · Fase **F6 · Design System**

> Especificación accionable para que **Claude Code** monte el Storybook del frontend. Define configuración, convenciones y el inventario de stories. Basado en las decisiones de F6 (MUI · tema claro/oscuro índigo · TanStack Query).

| Campo | Valor |
|---|---|
| **Estado** | Estable · v1.0 |
| **Fecha** | 2026-06-22 |
| **Storybook** | v10 (`@storybook/react-vite`) |
| **Ejecutor** | Claude Code (en `apps/web`) |

## 1. Stack y dependencias

- **Storybook 10** con framework `@storybook/react-vite` (builder Vite).
- **Addons:** `@storybook/addon-a11y` (accesibilidad), `@storybook/addon-themes` (toggle claro/oscuro), `@storybook/addon-docs` (autodocs), controls/viewport (incluidos).
- **UI:** MUI (`@mui/material`, `@emotion/*`), tema del proyecto.

Instalación: `npx storybook@latest init` en `apps/web` (configura Vite automáticamente) + addons a11y y themes.

## 2. Configuración

**`.storybook/main.ts`**

- `framework: '@storybook/react-vite'`
- `stories: ['../src/**/*.stories.@(ts|tsx)', '../src/**/*.mdx']`
- `addons: [a11y, themes, docs]`

**`.storybook/preview.tsx`**

- Decorador global con **`ThemeProvider`** de MUI + `CssBaseline`.
- `withThemeFromJSXProvider` (addon-themes) con los temas `light` y `dark` del proyecto → toggle en la toolbar.
- `parameters.a11y` activado; `tags: ['autodocs']` global.

> El tema claro/oscuro debe ser **el mismo** `createTheme` que usa la app (no uno aparte), para que el Storybook refleje la realidad.

## 3. Convenciones de stories

| Aspecto | Convención |
|---|---|
| Formato | **CSF3** (Component Story Format 3): `meta` por defecto + stories como objetos `{ args }` |
| Ubicación | `Componente.stories.tsx` junto al componente |
| Jerarquía | `title` en grupos: `Foundations/*`, `Molecules/*`, `Organisms/*` |
| Docs | `tags: ['autodocs']` para documentación automática |
| Controls | `argTypes` para props con enums (status, priority) como `select` |
| Interacciones | `play()` con `@storybook/test` para estados de formulario donde aplique |

## 4. Foundations (stories sin componente)

| Story | Contenido |
|---|---|
| `Foundations/Colors` | Paleta primario (índigo), secundario y semánticos (`success/warning/error/info`) en claro y oscuro |
| `Foundations/Typography` | Escala tipográfica de MUI (h1–caption) |
| `Foundations/Spacing` | Múltiplos de 8 px (`theme.spacing`) |
| `Foundations/StatusColors` | Mapa estado → color del Chip (tabla visual) |
| `Foundations/PriorityColors` | Mapa prioridad → color del Chip |

## 5. Inventario de componentes y stories

### Moléculas

| Componente | Props clave | Stories | Notas a11y |
|---|---|---|---|
| **StatusChip** | `status` | `Open`, `InProgress`, `Resolved`, `Closed`, `Reopened`, `AllStatuses` | No depender solo del color: incluir texto del estado |
| **PriorityChip** | `priority` | `Low`, `Medium`, `High`, `Urgent`, `AllPriorities` | Texto + icono opcional; contraste AA |
| **SearchBar** | `value`, `onChange`, `onSubmit` | `Default`, `WithValue`, `Disabled` | `label`/`aria-label`; submit por Enter |
| **CursorPagination** | `hasMore`, `loading`, `onNext`, `onPrev` | `FirstPage`, `Middle`, `LastPage`, `Loading` | Botones con nombre accesible; estado `disabled` |
| **FormField** | `label`, `error`, `required` | `Default`, `Required`, `WithError`, `Disabled` | Label asociada; error con `aria-describedby` |

### Organismos

| Componente | Props clave | Stories | Notas a11y |
|---|---|---|---|
| **TicketCard** | `ticket` | `Open`, `Urgent`, `Assigned`, `Unassigned`, `LongTitle` | Encabezado semántico; chips con texto |
| **TicketTable** | `tickets`, `loading` | `WithData`, `Loading`, `Empty`, `Error` | Tabla con cabeceras `<th scope>`; estados anunciados |
| **TicketForm** | `mode`, `initialValues`, `onSubmit` | `Create`, `Edit`, `WithValidationErrors`, `Submitting` | Foco al primer campo; errores `aria-live` (usar `play()`) |
| **CommentList** | `comments` | `WithComments`, `Empty` | Lista semántica; autor y fecha legibles |
| **AppLayout** | `role`, `children` | `AsClient`, `AsAgent`, `AsAdmin`, `DarkMode` | Navegación por teclado; `nav` con landmarks |

## 6. Temas claro / oscuro

- Toggle en la toolbar (addon-themes); ambos temas se documentan en autodocs.
- Toda story debe verse correcta en claro y oscuro (criterio de revisión).
- Story de control: `AppLayout/DarkMode` fija el tema oscuro por defecto.

## 7. Accesibilidad

- `addon-a11y` activo en todas las stories; objetivo **WCAG 2.1 AA**.
- Cada componente revisa: contraste, nombres accesibles, foco visible y navegación por teclado.
- Recomendado el **test-runner** de Storybook para automatizar checks de a11y en CI.

## 8. Datos mock

Crear `src/test/factories.ts` con `mockTicket(overrides)`, `mockUser(role)`, `mockComment()` para alimentar las stories de forma consistente y reutilizable.

## 9. Checklist de implementación (para Claude Code)

1. Scaffold `apps/web` (Vite + React 19 + TS) si no existe.
2. Instalar MUI + emotion + TanStack Query + React Router.
3. `npx storybook@latest init` (builder Vite) + addons `a11y` y `themes`.
4. Implementar el tema (`createTheme` claro/oscuro índigo) reutilizable por app y Storybook.
5. Configurar `preview.tsx` (ThemeProvider + CssBaseline + toggle de tema).
6. Crear las stories `Foundations/*` (tokens).
7. Implementar cada componente del §5 con su `*.stories.tsx`.
8. Añadir `factories.ts` para datos mock.
9. `npm run storybook` para iterar visualmente.
10. `npm run build-storybook` en **CI** (job aparte); opcional `test-runner` (a11y/interacciones) y/o Chromatic para regresión visual.

## 10. Pendiente de iterar

- Confirmar si se añade Chromatic (regresión visual) o solo build estático en CI.
- Decidir si las **vistas** (Login, Detalle) tienen stories o solo los componentes del design system.
- Afinar `argTypes` y controles por componente durante la implementación.
