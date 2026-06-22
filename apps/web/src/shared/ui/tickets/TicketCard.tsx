// Tarjeta de ticket (organismo presentacional) compuesta sobre StatusChip/PriorityChip.
import Card from '@mui/material/Card';
import CardActionArea from '@mui/material/CardActionArea';
import CardContent from '@mui/material/CardContent';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import type { Ticket } from '../../api/types';
import { formatDate } from '../../lib/format';
import { StatusChip } from '../data-display/StatusChip';
import { PriorityChip } from '../data-display/PriorityChip';

export interface TicketCardProps {
  ticket: Ticket;
  onClick?: () => void;
}

export function TicketCard({ ticket, onClick }: TicketCardProps) {
  const body = (
    <CardContent>
      <Stack direction="row" spacing={1} sx={{ mb: 1, flexWrap: 'wrap', alignItems: 'center' }}>
        <StatusChip status={ticket.status} />
        <PriorityChip priority={ticket.priority} />
      </Stack>
      <Typography variant="h6" component="h3" gutterBottom>
        {ticket.title}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        {ticket.description}
      </Typography>
      <Stack direction="row" spacing={2} sx={{ flexWrap: 'wrap', color: 'text.secondary' }}>
        <Typography variant="caption">Solicita: {ticket.requesterName}</Typography>
        <Typography variant="caption">Agente: {ticket.assigneeName ?? 'Sin asignar'}</Typography>
        <Typography variant="caption">Creado: {formatDate(ticket.createdAt)}</Typography>
      </Stack>
    </CardContent>
  );

  return (
    <Card variant="outlined">
      {onClick !== undefined ? <CardActionArea onClick={onClick}>{body}</CardActionArea> : body}
    </Card>
  );
}
