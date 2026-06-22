// Detalle de ticket. Compone PageContainer + chips + CommentList + acciones por rol (ConfirmDialog/Snackbar).
// DEMO (F3): datos mock. En F6 se reemplaza por useTicket + mutaciones (TanStack Query).
import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Button from '@mui/material/Button';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Divider from '@mui/material/Divider';
import { PageContainer } from '../../shared/ui/layout/PageContainer';
import { StatusChip } from '../../shared/ui/data-display/StatusChip';
import { PriorityChip } from '../../shared/ui/data-display/PriorityChip';
import { CommentList } from '../../shared/ui/tickets/CommentList';
import { ConfirmDialog } from '../../shared/ui/feedback/ConfirmDialog';
import { useNotify } from '../../shared/ui/feedback/notifications-context';
import { mockComment, mockTicket } from '../../test/factories';
import { ROUTES } from '../../app/router/routes';
import { useAuth } from '../auth/auth-context';

export function TicketDetailView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const { notify } = useNotify();

  const ticket = useMemo(() => mockTicket({ id: id ?? 'demo', assigneeName: null, assigneeId: null }), [id]);
  const comments = useMemo(
    () => [mockComment(), mockComment({ body: 'Estamos revisando el problema, gracias por reportarlo.' })],
    [],
  );

  const [resolveOpen, setResolveOpen] = useState(false);
  const canManage = hasRole('agent', 'admin');

  return (
    <PageContainer
      title={ticket.title}
      actions={
        <Button variant="text" onClick={() => navigate(ROUTES.tickets)}>
          Volver al listado
        </Button>
      }
    >
      <Stack spacing={3}>
        <Paper variant="outlined" sx={{ p: 3 }}>
          <Stack direction="row" spacing={1} sx={{ mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
            <StatusChip status={ticket.status} />
            <PriorityChip priority={ticket.priority} />
            <Typography variant="caption" color="text.secondary">
              Solicita: {ticket.requesterName}
            </Typography>
          </Stack>
          <Typography variant="body1">{ticket.description}</Typography>

          {canManage ? (
            <>
              <Divider sx={{ my: 2 }} />
              <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap' }}>
                <Button variant="outlined" onClick={() => setResolveOpen(true)}>
                  Resolver
                </Button>
                <Button variant="outlined" onClick={() => notify('Ticket asignado a ti (demo).', 'success')}>
                  Asignarme
                </Button>
              </Stack>
            </>
          ) : null}
        </Paper>

        <section aria-labelledby="comments-heading">
          <Typography id="comments-heading" variant="h6" component="h2" gutterBottom>
            Comentarios
          </Typography>
          <CommentList comments={comments} />
        </section>
      </Stack>

      <ConfirmDialog
        open={resolveOpen}
        title="Resolver ticket"
        message="¿Marcar este ticket como resuelto?"
        confirmLabel="Resolver"
        onCancel={() => setResolveOpen(false)}
        onConfirm={() => {
          setResolveOpen(false);
          notify('Ticket marcado como resuelto (demo).', 'success');
        }}
      />
    </PageContainer>
  );
}
