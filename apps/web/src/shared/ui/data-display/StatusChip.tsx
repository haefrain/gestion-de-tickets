// Chip de estado del ticket. Color y etiqueta vienen de los tokens (nada hardcodeado).
// a11y: muestra texto del estado, no depende solo del color (storybook-spec.md §5).

import Chip from '@mui/material/Chip';
import type { TicketStatus } from '../../api/types';
import { STATUS_COLOR, STATUS_LABEL } from '../../theme/tokens';

export interface StatusChipProps {
  status: TicketStatus;
  size?: 'small' | 'medium';
}

export function StatusChip({ status, size = 'small' }: StatusChipProps) {
  return <Chip label={STATUS_LABEL[status]} color={STATUS_COLOR[status]} size={size} variant="filled" />;
}
