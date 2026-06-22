// Chip de prioridad. Variante outlined para distinguirlo visualmente del StatusChip.
// a11y: texto + color con contraste AA en ambos temas.

import Chip from '@mui/material/Chip';
import type { Priority } from '../../api/types';
import { PRIORITY_COLOR, PRIORITY_LABEL } from '../../theme/tokens';

export interface PriorityChipProps {
  priority: Priority;
  size?: 'small' | 'medium';
}

export function PriorityChip({ priority, size = 'small' }: PriorityChipProps) {
  return (
    <Chip label={PRIORITY_LABEL[priority]} color={PRIORITY_COLOR[priority]} size={size} variant="outlined" />
  );
}
