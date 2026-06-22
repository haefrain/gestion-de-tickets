// Factories de datos mock, reutilizables por stories y tests (docs/design-system/storybook-spec.md §8).
// Deterministas (sin Math.random ni Date.now reales) para que stories y snapshots sean estables.

import type { Comment, Priority, Role, Ticket, TicketStatus, User } from '../shared/api/types';

let seq = 0;
/** Contador determinista para generar ids/sufijos estables dentro de una misma ejecución. */
const nextSeq = (): number => (seq += 1);

const pad = (n: number): string => n.toString().padStart(4, '0');

export function mockUser(role: Role = 'client', overrides: Partial<User> = {}): User {
  const n = nextSeq();
  const names: Record<Role, string> = {
    client: 'Cliente Demo',
    agent: 'Agente Demo',
    admin: 'Admin Demo',
  };
  return {
    id: `0190f000-0000-7000-8000-${pad(n)}00000000`,
    name: names[role],
    email: `${role}${n}@tickets.local`,
    role,
    ...overrides,
  };
}

export function mockComment(overrides: Partial<Comment> = {}): Comment {
  const n = nextSeq();
  return {
    id: `0190c000-0000-7000-8000-${pad(n)}00000000`,
    ticketId: '0190t000-0000-7000-8000-000000000000',
    authorId: '0190f000-0000-7000-8000-000100000000',
    authorName: 'Agente Demo',
    body: 'Comentario de ejemplo sobre el ticket.',
    createdAt: '2026-06-22T10:15:00Z',
    ...overrides,
  };
}

export function mockTicket(overrides: Partial<Ticket> = {}): Ticket {
  const n = nextSeq();
  const status: TicketStatus = 'open';
  const priority: Priority = 'medium';
  return {
    id: `0190t000-0000-7000-8000-${pad(n)}00000000`,
    title: 'No puedo iniciar sesión',
    description: 'Recibo un error 500 al intentar entrar con mis credenciales.',
    status,
    priority,
    category: 'auth',
    requesterId: '0190f000-0000-7000-8000-000000000000',
    requesterName: 'Cliente Demo',
    assigneeId: null,
    assigneeName: null,
    createdAt: '2026-06-22T10:00:00Z',
    updatedAt: '2026-06-22T10:00:00Z',
    ...overrides,
  };
}

/** Lista determinista de tickets variados para stories de tablas/listados. */
export function mockTickets(count = 5): Ticket[] {
  const variants: Array<Partial<Ticket>> = [
    { status: 'open', priority: 'high', title: 'Error al adjuntar archivos' },
    {
      status: 'in_progress',
      priority: 'urgent',
      title: 'Caída del checkout',
      assigneeId: '0190f000-0000-7000-8000-000100000000',
      assigneeName: 'Agente Demo',
    },
    { status: 'resolved', priority: 'medium', title: 'Typo en la página de inicio' },
    { status: 'closed', priority: 'low', title: 'Consulta sobre facturación' },
    { status: 'reopened', priority: 'high', title: 'La búsqueda no devuelve resultados' },
  ];
  return Array.from({ length: count }, (_, i) => mockTicket(variants[i % variants.length]));
}
