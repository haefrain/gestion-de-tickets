# Design System — Frontend

**Prueba Técnica · IATSAE** · Fase **F6 · Design System**

| Campo | Valor |
|---|---|
| **Estado** | Borrador v0.1 |
| **Fecha** | 2026-06-22 |
| **Base** | MUI (Material UI) · React 19 · TypeScript · Vite |
| **Datos** | TanStack Query · **Tema** claro + oscuro (índigo) · **Storybook** |

## 1. Principios

- **Consistencia:** todo componente parte de los tokens del tema MUI; nada de estilos sueltos.
- **Accesibilidad:** objetivo **WCAG 2.1 AA**.
- **Reutilización:** componentes de dominio (TicketCard, StatusChip) construidos sobre primitivas MUI.
- **El backend manda:** el frontend es capa de apoyo; prioriza claridad sobre vistosidad.

## 2. Design tokens (MUI theme)

Definidos una vez en `createTheme` y consumidos por todos los componentes.

| Token | Valor base |
|---|---|
| Color primario | Índigo (paleta MUI por defecto) |
| Color secundario | Definido en el tema |
| Semánticos | `success`, `warning`, `error`, `info` |
| Tipografía | Roboto / system-ui; escala tipográfica de MUI |
| Espaciado | Múltiplos de 8 px (`theme.spacing`) |
| Radio | `shape.borderRadius` (p. ej. 8 px) |
| Modo | `light` / `dark` con toggle |

## 3. Tema claro / oscuro

- Un solo `ThemeProvider`; el modo se alterna con un toggle y se persiste en memoria de sesión.
- Las paletas claro/oscuro derivan del mismo primario índigo para mantener identidad.
- Todos los componentes deben verse correctos en ambos modos (criterio de revisión).

## 4. Mapeo de estado y prioridad a color

| Estado | Color (Chip) | Prioridad | Color (Chip) |
|---|---|---|---|
| `open` | info | `low` | default |
| `in_progress` | warning | `medium` | info |
| `resolved` | success | `high` | warning |
| `closed` | default | `urgent` | error |
| `reopened` | secondary | | |

## 5. Inventario de componentes

| Nivel | Componentes |
|---|---|
| **Primitivas** (MUI) | Button, TextField, Select, Checkbox, Dialog, Snackbar, Table |
| **Moléculas** (dominio) | `StatusChip`, `PriorityChip`, `SearchBar`, `CursorPagination`, `FormField` |
| **Organismos** | `TicketCard`, `TicketTable`, `TicketForm`, `CommentList`, `AppLayout` (AppBar + nav) |
| **Vistas** | Login, Registro, Listado de tickets, Detalle de ticket, Panel |

## 6. Patrones de UI

- **Estados de datos:** cada vista maneja *loading*, *error* y *empty* de forma explícita (apoyado en TanStack Query).
- **Formularios:** validación en cliente + mapeo de errores RFC 7807 del backend a los campos.
- **Notificaciones:** `Snackbar`/toast para resultados de acciones (con `aria-live`).
- **Confirmaciones:** `Dialog` para acciones destructivas o transiciones de estado.

## 7. Accesibilidad (WCAG 2.1 AA)

- Contraste mínimo 4.5:1 (verificado en ambos temas).
- Foco visible y navegación completa por teclado.
- Etiquetas asociadas a los campos; roles y `aria-*` en componentes interactivos.
- Mensajes de estado mediante regiones `aria-live`.

## 8. Consumo de API y JWT

- **`apiClient`** centraliza base URL, cabecera `Authorization: Bearer`, y normaliza errores RFC 7807.
- **`AuthProvider`** (Context) expone sesión, login/logout y refresco transparente del token.
- **TanStack Query** gestiona las consultas (`useTickets`, `useTicket`) con cache, reintentos y revalidación.
- **Rutas protegidas:** componente `RequireAuth` que exige sesión y rol; sin sesión redirige a login.

## 9. Estructura del frontend

```text
apps/web/src/
├── app/                 # Router, providers (Theme, Auth, QueryClient)
├── shared/
│   ├── theme/           # createTheme (tokens, claro/oscuro)
│   ├── api/             # apiClient, tipos
│   └── ui/              # componentes del design system
├── features/
│   ├── auth/            # login, registro, AuthProvider, hooks
│   └── tickets/         # listado, detalle, formulario, queries
└── main.tsx
```

## 10. Storybook

- Cada componente del design system tiene su *story* con variantes (estados, temas claro/oscuro).
- Sirve como documentación viva y banco de pruebas visual/accesibilidad.

## 11. Pendiente de iterar

- Confirmar color secundario y tipografía final.
- Decidir gestor de formularios (React Hook Form) si la validación crece.
- Definir alcance de cobertura de Storybook (solo design system vs también vistas).
