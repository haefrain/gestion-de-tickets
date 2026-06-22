import type { Meta, StoryObj } from '@storybook/react-vite';
import Stack from '@mui/material/Stack';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import { PRIORITIES } from '../shared/api/types';
import { PRIORITY_COLOR } from '../shared/theme/tokens';
import { PriorityChip } from '../shared/ui/data-display/PriorityChip';

function PriorityColorTable() {
  return (
    <Stack spacing={1.5}>
      {PRIORITIES.map((priority) => (
        <Stack key={priority} direction="row" spacing={2} sx={{ alignItems: 'center' }}>
          <Box sx={{ width: 140 }}>
            <Typography variant="body2" sx={{ fontFamily: 'monospace' }}>
              {priority}
            </Typography>
          </Box>
          <PriorityChip priority={priority} />
          <Typography variant="caption" color="text.secondary">
            color: {PRIORITY_COLOR[priority]}
          </Typography>
        </Stack>
      ))}
    </Stack>
  );
}

const meta = { title: 'Foundations/PriorityColors' } satisfies Meta;
export default meta;
type Story = StoryObj;

export const Map: Story = { name: 'Prioridad → color', render: () => <PriorityColorTable /> };
