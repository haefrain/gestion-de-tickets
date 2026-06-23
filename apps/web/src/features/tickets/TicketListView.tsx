// Listado de tickets conectado a la API. Filtros server-side (estado/prioridad) y modo búsqueda:
// si hay texto, consulta /search/tickets (relevancia); si no, /tickets. Paginación por cursor.
import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Box from '@mui/material/Box';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import MenuItem from '@mui/material/MenuItem';
import Alert from '@mui/material/Alert';
import AddIcon from '@mui/icons-material/Add';
import { PageContainer } from '../../shared/ui/layout/PageContainer';
import { SearchBar } from '../../shared/ui/inputs/SearchBar';
import { DataState } from '../../shared/ui/feedback/DataState';
import { TicketTable } from '../../shared/ui/tickets/TicketTable';
import { CursorPagination } from '../../shared/ui/data-display/CursorPagination';
import { FormField } from '../../shared/ui/inputs/FormField';
import { PRIORITIES, TICKET_STATUSES } from '../../shared/api/types';
import type { Priority, TicketStatus } from '../../shared/api/types';
import { PRIORITY_LABEL, STATUS_LABEL } from '../../shared/theme/tokens';
import { ROUTES } from '../../app/router/routes';
import { useCreateTicket, useSearchTickets, useTickets } from './api';

export function TicketListView() {
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState<TicketStatus | ''>('');
  const [priority, setPriority] = useState<Priority | ''>('');
  const [cursors, setCursors] = useState<string[]>([]);
  const cursor = cursors.at(-1);

  const isSearching = query.trim() !== '';
  const listResult = useTickets({ cursor, status, priority }, !isSearching);
  const searchResult = useSearchTickets({ q: query, cursor, status, priority }, isSearching);
  const active = isSearching ? searchResult : listResult;

  const tickets = active.data?.data ?? [];
  const [creating, setCreating] = useState(false);

  function resetPaging(): void {
    setCursors([]);
  }

  return (
    <PageContainer
      title="Tickets"
      actions={
        <Button variant="contained" startIcon={<AddIcon />} onClick={() => setCreating(true)}>
          Nuevo ticket
        </Button>
      }
    >
      <Stack spacing={2}>
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ alignItems: { sm: 'flex-start' } }}>
          <Box sx={{ flexGrow: 1 }}>
            <SearchBar
              value={query}
              onChange={(value) => {
                setQuery(value);
                resetPaging();
              }}
            />
          </Box>
          <Box sx={{ minWidth: 160 }}>
            <FormField
              name="status"
              label="Estado"
              value={status}
              select
              onChange={(value) => {
                setStatus(value as TicketStatus | '');
                resetPaging();
              }}
            >
              <MenuItem value="">Todos</MenuItem>
              {TICKET_STATUSES.map((value) => (
                <MenuItem key={value} value={value}>
                  {STATUS_LABEL[value]}
                </MenuItem>
              ))}
            </FormField>
          </Box>
          <Box sx={{ minWidth: 160 }}>
            <FormField
              name="priority"
              label="Prioridad"
              value={priority}
              select
              onChange={(value) => {
                setPriority(value as Priority | '');
                resetPaging();
              }}
            >
              <MenuItem value="">Todas</MenuItem>
              {PRIORITIES.map((value) => (
                <MenuItem key={value} value={value}>
                  {PRIORITY_LABEL[value]}
                </MenuItem>
              ))}
            </FormField>
          </Box>
        </Stack>

        <DataState
          loading={active.isLoading}
          error={active.isError ? 'No se pudieron cargar los tickets.' : null}
          empty={tickets.length === 0}
          emptyMessage="No hay tickets que coincidan con la búsqueda."
        >
          <TicketTable tickets={tickets} onRowClick={(ticket) => navigate(ROUTES.ticketDetail(ticket.id))} />
        </DataState>

        <CursorPagination
          hasMore={active.data?.page.hasMore ?? false}
          hasPrev={cursors.length > 0}
          onNext={() => {
            const next = active.data?.page.nextCursor;
            if (next !== null && next !== undefined) setCursors((prev) => [...prev, next]);
          }}
          onPrev={() => setCursors((prev) => prev.slice(0, -1))}
        />
      </Stack>

      <CreateTicketDialog open={creating} onClose={() => setCreating(false)} />
    </PageContainer>
  );
}

function CreateTicketDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const create = useCreateTicket();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [priority, setPriority] = useState<Priority>('medium');

  function handleSubmit(event: FormEvent): void {
    event.preventDefault();
    create.mutate(
      { title, description, priority },
      {
        onSuccess: () => {
          setTitle('');
          setDescription('');
          setPriority('medium');
          onClose();
        },
      },
    );
  }

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
      <Box component="form" onSubmit={handleSubmit}>
        <DialogTitle>Nuevo ticket</DialogTitle>
        <DialogContent>
          {create.isError ? (
            <Alert severity="error" sx={{ mb: 1 }}>
              No se pudo crear el ticket.
            </Alert>
          ) : null}
          <FormField name="title" label="Título" value={title} onChange={setTitle} required autoFocus />
          <FormField
            name="description"
            label="Descripción"
            value={description}
            onChange={setDescription}
            required
            multiline
            rows={4}
          />
          <FormField
            name="priority"
            label="Prioridad"
            value={priority}
            onChange={(value) => setPriority(value as Priority)}
            select
          >
            {PRIORITIES.map((value) => (
              <MenuItem key={value} value={value}>
                {PRIORITY_LABEL[value]}
              </MenuItem>
            ))}
          </FormField>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose} disabled={create.isPending}>
            Cancelar
          </Button>
          <Button type="submit" variant="contained" disabled={create.isPending}>
            Crear
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  );
}
