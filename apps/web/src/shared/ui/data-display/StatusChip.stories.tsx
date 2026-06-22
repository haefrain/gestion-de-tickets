import type { Meta, StoryObj } from '@storybook/react-vite';
import Stack from '@mui/material/Stack';
import { StatusChip } from './StatusChip';
import { TICKET_STATUSES } from '../../api/types';

const meta = {
  title: 'Molecules/StatusChip',
  component: StatusChip,
  args: { status: 'open' },
  argTypes: { status: { control: 'select', options: TICKET_STATUSES } },
} satisfies Meta<typeof StatusChip>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Open: Story = { args: { status: 'open' } };
export const InProgress: Story = { args: { status: 'in_progress' } };
export const Resolved: Story = { args: { status: 'resolved' } };
export const Closed: Story = { args: { status: 'closed' } };
export const Reopened: Story = { args: { status: 'reopened' } };

export const AllStatuses: Story = {
  render: () => (
    <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap' }}>
      {TICKET_STATUSES.map((status) => (
        <StatusChip key={status} status={status} />
      ))}
    </Stack>
  ),
};
