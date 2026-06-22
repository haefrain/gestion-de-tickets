// Tipos de dominio del frontend. Reflejan el contrato de la API (docs/api/api-design.md)
// y el modelo de Ticketing (docs/architecture/overview.md). El backend manda.

export const TICKET_STATUSES = ['open', 'in_progress', 'resolved', 'closed', 'reopened'] as const;
export type TicketStatus = (typeof TICKET_STATUSES)[number];

export const PRIORITIES = ['low', 'medium', 'high', 'urgent'] as const;
export type Priority = (typeof PRIORITIES)[number];

export const ROLES = ['client', 'agent', 'admin'] as const;
export type Role = (typeof ROLES)[number];

/** UUID v7 en string (los IDs del backend). */
export type Id = string;

/** Fecha ISO 8601 UTC en string. */
export type IsoDateTime = string;

export interface User {
  id: Id;
  name: string;
  email: string;
  role: Role;
}

export interface Comment {
  id: Id;
  ticketId: Id;
  authorId: Id;
  authorName: string;
  body: string;
  createdAt: IsoDateTime;
}

export interface Ticket {
  id: Id;
  title: string;
  description: string;
  status: TicketStatus;
  priority: Priority;
  category: string;
  requesterId: Id;
  requesterName: string;
  assigneeId: Id | null;
  assigneeName: string | null;
  createdAt: IsoDateTime;
  updatedAt: IsoDateTime;
}

/** Página por cursor (docs/api/api-design.md §5). */
export interface CursorPage<T> {
  data: T[];
  page: {
    limit: number;
    nextCursor: string | null;
    hasMore: boolean;
  };
}
