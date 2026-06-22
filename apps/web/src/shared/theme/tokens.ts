// Mapeo único estado/prioridad -> color del Chip y etiqueta legible.
// Fuente: docs/design-system/overview.md §4. Se consume desde StatusChip, PriorityChip
// y las stories Foundations/StatusColors y Foundations/PriorityColors. Nada hardcodeado en componentes.

import type { Priority, TicketStatus } from '../api/types';

/** Colores válidos de un MUI Chip (evita acoplar este módulo a la versión de MUI). */
export type ChipColor = 'default' | 'primary' | 'secondary' | 'error' | 'info' | 'success' | 'warning';

export const STATUS_COLOR: Record<TicketStatus, ChipColor> = {
  open: 'info',
  in_progress: 'warning',
  resolved: 'success',
  closed: 'default',
  reopened: 'secondary',
};

export const STATUS_LABEL: Record<TicketStatus, string> = {
  open: 'Abierto',
  in_progress: 'En progreso',
  resolved: 'Resuelto',
  closed: 'Cerrado',
  reopened: 'Reabierto',
};

export const PRIORITY_COLOR: Record<Priority, ChipColor> = {
  low: 'default',
  medium: 'info',
  high: 'warning',
  urgent: 'error',
};

export const PRIORITY_LABEL: Record<Priority, string> = {
  low: 'Baja',
  medium: 'Media',
  high: 'Alta',
  urgent: 'Urgente',
};

export const ROLE_LABEL = {
  client: 'Cliente',
  agent: 'Agente',
  admin: 'Administrador',
} as const;
