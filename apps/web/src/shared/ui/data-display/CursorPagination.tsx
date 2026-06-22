// Paginación por cursor (docs/api/api-design.md §5). Botones con nombre accesible y estado disabled.

import Stack from '@mui/material/Stack';
import Button from '@mui/material/Button';
import CircularProgress from '@mui/material/CircularProgress';
import ChevronLeftIcon from '@mui/icons-material/ChevronLeft';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';

export interface CursorPaginationProps {
  hasMore: boolean;
  hasPrev?: boolean;
  loading?: boolean;
  onNext: () => void;
  onPrev: () => void;
}

export function CursorPagination({
  hasMore,
  hasPrev = false,
  loading = false,
  onNext,
  onPrev,
}: CursorPaginationProps) {
  return (
    <Stack direction="row" spacing={1} sx={{ justifyContent: 'flex-end', alignItems: 'center' }}>
      {loading ? <CircularProgress size={18} aria-label="Cargando" /> : null}
      <Button onClick={onPrev} disabled={!hasPrev || loading} startIcon={<ChevronLeftIcon />}>
        Anterior
      </Button>
      <Button onClick={onNext} disabled={!hasMore || loading} endIcon={<ChevronRightIcon />}>
        Siguiente
      </Button>
    </Stack>
  );
}
