// Detalle de ticket conectado a la API (useTicket) con acciones de transición por rol.
// La matriz de transiciones refleja la máquina de estados del backend; el backend valida (409).
import { useNavigate, useParams } from 'react-router-dom';
import Button from '@mui/material/Button';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Divider from '@mui/material/Divider';
import { PageContainer } from '../../shared/ui/layout/PageContainer';
import { DataState } from '../../shared/ui/feedback/DataState';
import { StatusChip } from '../../shared/ui/data-display/StatusChip';
import { PriorityChip } from '../../shared/ui/data-display/PriorityChip';
import { useNotify } from '../../shared/ui/feedback/notifications-context';
import type { TicketStatus } from '../../shared/api/types';
import { ROUTES } from '../../app/router/routes';
import { useAuth } from '../auth/auth-context';
import { useTicket, useTransitionTicket } from './api';

const TRANSITIONS: Record<TicketStatus, { to: TicketStatus; label: string }[]> = {
  open: [
    { to: 'in_progress', label: 'Tomar' },
    { to: 'closed', label: 'Cerrar' },
  ],
  in_progress: [
    { to: 'resolved', label: 'Resolver' },
    { to: 'closed', label: 'Cerrar' },
  ],
  resolved: [
    { to: 'closed', label: 'Cerrar' },
    { to: 'reopened', label: 'Reabrir' },
  ],
  closed: [{ to: 'reopened', label: 'Reabrir' }],
  reopened: [
    { to: 'in_progress', label: 'Tomar' },
    { to: 'closed', label: 'Cerrar' },
  ],
};

function Meta({ label, value }: { label: string; value: string }) {
  return (
    <Box>
      <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
        {label}
      </Typography>
      <Typography variant="body2">{value}</Typography>
    </Box>
  );
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString('es', { dateStyle: 'medium', timeStyle: 'short' });
}

export function TicketDetailView() {
  const { id = '' } = useParams();
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const { notify } = useNotify();
  const { data: ticket, isLoading, isError } = useTicket(id);
  const transition = useTransitionTicket(id);

  const canManage = hasRole('agent', 'admin');

  return (
    <PageContainer
      title={ticket?.title ?? 'Ticket'}
      actions={
        <Button variant="text" onClick={() => navigate(ROUTES.tickets)}>
          Volver al listado
        </Button>
      }
    >
      <DataState loading={isLoading} error={isError ? 'No se pudo cargar el ticket.' : null}>
        {ticket ? (
          <Stack spacing={3}>
            <Paper variant="outlined" sx={{ p: 3 }}>
              <Stack direction="row" spacing={1} sx={{ mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
                <StatusChip status={ticket.status} />
                <PriorityChip priority={ticket.priority} />
              </Stack>

              <Typography variant="body1" sx={{ mb: 3, whiteSpace: 'pre-wrap' }}>
                {ticket.description}
              </Typography>

              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns: { xs: '1fr 1fr', sm: 'repeat(4, 1fr)' },
                  gap: 2,
                }}
              >
                <Meta label="Solicitante" value={ticket.requesterName} />
                <Meta label="Asignado" value={ticket.assigneeName ?? 'Sin asignar'} />
                <Meta label="Categoría" value={ticket.category} />
                <Meta label="Creado" value={formatDate(ticket.createdAt)} />
              </Box>

              {canManage ? (
                <>
                  <Divider sx={{ my: 2 }} />
                  <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap' }}>
                    {TRANSITIONS[ticket.status].map(({ to, label }) => (
                      <Button
                        key={to}
                        variant="outlined"
                        disabled={transition.isPending}
                        onClick={() =>
                          transition.mutate(to, {
                            onSuccess: () => notify(`Ticket actualizado: ${label.toLowerCase()}.`, 'success'),
                            onError: () => notify('No se pudo cambiar el estado.', 'error'),
                          })
                        }
                      >
                        {label}
                      </Button>
                    ))}
                  </Stack>
                </>
              ) : null}
            </Paper>
          </Stack>
        ) : null}
      </DataState>
    </PageContainer>
  );
}
