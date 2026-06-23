// Capa de datos de tickets: llama al apiClient y mapea el contrato del backend (snake_case)
// a los tipos de dominio del front (camelCase). Las vistas consumen los hooks de abajo.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../../shared/api/apiClient';
import type { CursorPage, Priority, Ticket, TicketStatus } from '../../shared/api/types';

interface RawTicket {
  id: string;
  title: string;
  description: string;
  status: string;
  priority: string;
  category: string;
  requester_id: string;
  requester_name: string;
  assignee_id: string | null;
  assignee_name: string | null;
  created_at: string;
  updated_at: string;
}

interface RawPage {
  data: RawTicket[];
  page: { limit: number; next_cursor: string | null; has_more: boolean };
}

function toTicket(raw: RawTicket): Ticket {
  return {
    id: raw.id,
    title: raw.title,
    description: raw.description,
    status: raw.status as TicketStatus,
    priority: raw.priority as Priority,
    category: raw.category,
    requesterId: raw.requester_id,
    requesterName: raw.requester_name,
    assigneeId: raw.assignee_id,
    assigneeName: raw.assignee_name,
    createdAt: raw.created_at,
    updatedAt: raw.updated_at,
  };
}

export interface TicketFilters {
  cursor?: string;
  status?: TicketStatus | '';
  priority?: Priority | '';
}

export interface CreateTicketInput {
  title: string;
  description: string;
  priority?: Priority;
  category?: string;
}

const ticketsKey = ['tickets'] as const;

async function fetchTickets(filters: TicketFilters): Promise<CursorPage<Ticket>> {
  const qs = new URLSearchParams({ limit: '20' });
  if (filters.cursor) qs.set('cursor', filters.cursor);
  if (filters.status) qs.set('status', filters.status);
  if (filters.priority) qs.set('priority', filters.priority);

  const raw = await apiClient.get<RawPage>(`/tickets?${qs.toString()}`);
  return {
    data: raw.data.map(toTicket),
    page: { limit: raw.page.limit, nextCursor: raw.page.next_cursor, hasMore: raw.page.has_more },
  };
}

export function useTickets(filters: TicketFilters) {
  return useQuery({
    queryKey: [...ticketsKey, 'list', filters],
    queryFn: () => fetchTickets(filters),
  });
}

export function useTicket(id: string) {
  return useQuery({
    queryKey: [...ticketsKey, 'detail', id],
    queryFn: async () => toTicket(await apiClient.get<RawTicket>(`/tickets/${id}`)),
    enabled: id !== '',
  });
}

export function useCreateTicket() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateTicketInput) => apiClient.post<RawTicket>('/tickets', input).then(toTicket),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ticketsKey }),
  });
}

export function useTransitionTicket(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (to: TicketStatus) =>
      apiClient.post<RawTicket>(`/tickets/${id}/transitions`, { to }).then(toTicket),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ticketsKey }),
  });
}
