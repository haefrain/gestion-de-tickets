// Listado de tickets. Compone PageContainer + SearchBar + DataState + TicketTable + CursorPagination.
// DEMO (F3): datos mock. En F6 se reemplaza por useTickets (TanStack Query) sin tocar la composición.
import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import AddIcon from '@mui/icons-material/Add';
import { PageContainer } from '../../shared/ui/layout/PageContainer';
import { SearchBar } from '../../shared/ui/inputs/SearchBar';
import { DataState } from '../../shared/ui/feedback/DataState';
import { TicketTable } from '../../shared/ui/tickets/TicketTable';
import { CursorPagination } from '../../shared/ui/data-display/CursorPagination';
import { mockTickets } from '../../test/factories';
import { ROUTES } from '../../app/router/routes';

export function TicketListView() {
  const navigate = useNavigate();
  const allTickets = useMemo(() => mockTickets(5), []);
  const [query, setQuery] = useState('');

  const filtered = allTickets.filter((ticket) => ticket.title.toLowerCase().includes(query.toLowerCase()));

  return (
    <PageContainer
      title="Tickets"
      actions={
        <Button variant="contained" startIcon={<AddIcon />}>
          Nuevo ticket
        </Button>
      }
    >
      <Stack spacing={2}>
        <SearchBar value={query} onChange={setQuery} />
        <DataState empty={filtered.length === 0} emptyMessage="No hay tickets que coincidan con la búsqueda.">
          <TicketTable tickets={filtered} onRowClick={(ticket) => navigate(ROUTES.ticketDetail(ticket.id))} />
        </DataState>
        <CursorPagination hasMore={false} hasPrev={false} onNext={() => undefined} onPrev={() => undefined} />
      </Stack>
    </PageContainer>
  );
}
