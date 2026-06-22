// Tabla de tickets (organismo presentacional). Cabeceras semánticas (th scope=col).
// Los estados loading/error/empty los aporta el envoltorio DataState en la vista (sin duplicar lógica).
import Table from '@mui/material/Table';
import TableHead from '@mui/material/TableHead';
import TableBody from '@mui/material/TableBody';
import TableRow from '@mui/material/TableRow';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import Paper from '@mui/material/Paper';
import Typography from '@mui/material/Typography';
import type { Ticket } from '../../api/types';
import { formatDate } from '../../lib/format';
import { StatusChip } from '../data-display/StatusChip';
import { PriorityChip } from '../data-display/PriorityChip';

export interface TicketTableProps {
  tickets: Ticket[];
  onRowClick?: (ticket: Ticket) => void;
}

const COLUMNS = ['Título', 'Estado', 'Prioridad', 'Solicitante', 'Agente', 'Creado'] as const;

export function TicketTable({ tickets, onRowClick }: TicketTableProps) {
  return (
    <TableContainer component={Paper} variant="outlined">
      <Table aria-label="Listado de tickets" size="small">
        <TableHead>
          <TableRow>
            {COLUMNS.map((column) => (
              <TableCell key={column} component="th" scope="col">
                {column}
              </TableCell>
            ))}
          </TableRow>
        </TableHead>
        <TableBody>
          {tickets.map((ticket) => (
            <TableRow
              key={ticket.id}
              hover
              onClick={onRowClick !== undefined ? () => onRowClick(ticket) : undefined}
              sx={{ cursor: onRowClick !== undefined ? 'pointer' : 'default' }}
            >
              <TableCell>{ticket.title}</TableCell>
              <TableCell>
                <StatusChip status={ticket.status} />
              </TableCell>
              <TableCell>
                <PriorityChip priority={ticket.priority} />
              </TableCell>
              <TableCell>{ticket.requesterName}</TableCell>
              <TableCell>
                {ticket.assigneeName ?? (
                  <Typography component="span" variant="body2" color="text.secondary">
                    Sin asignar
                  </Typography>
                )}
              </TableCell>
              <TableCell>{formatDate(ticket.createdAt)}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
}
