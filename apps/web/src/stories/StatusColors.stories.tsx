import type { Meta, StoryObj } from '@storybook/react-vite';
import Stack from '@mui/material/Stack';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import { TICKET_STATUSES } from '../shared/api/types';
import { STATUS_COLOR } from '../shared/theme/tokens';
import { StatusChip } from '../shared/ui/data-display/StatusChip';

function StatusColorTable() {
  return (
    <Stack spacing={1.5}>
      {TICKET_STATUSES.map((status) => (
        <Stack key={status} direction="row" spacing={2} sx={{ alignItems: 'center' }}>
          <Box sx={{ width: 140 }}>
            <Typography variant="body2" sx={{ fontFamily: 'monospace' }}>
              {status}
            </Typography>
          </Box>
          <StatusChip status={status} />
          <Typography variant="caption" color="text.secondary">
            color: {STATUS_COLOR[status]}
          </Typography>
        </Stack>
      ))}
    </Stack>
  );
}

const meta = { title: 'Foundations/StatusColors' } satisfies Meta;
export default meta;
type Story = StoryObj;

export const Map: Story = { name: 'Estado → color', render: () => <StatusColorTable /> };
