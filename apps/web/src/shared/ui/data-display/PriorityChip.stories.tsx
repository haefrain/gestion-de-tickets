import type { Meta, StoryObj } from '@storybook/react-vite';
import Stack from '@mui/material/Stack';
import { PriorityChip } from './PriorityChip';
import { PRIORITIES } from '../../api/types';

const meta = {
  title: 'Molecules/PriorityChip',
  component: PriorityChip,
  args: { priority: 'medium' },
  argTypes: { priority: { control: 'select', options: PRIORITIES } },
} satisfies Meta<typeof PriorityChip>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Low: Story = { args: { priority: 'low' } };
export const Medium: Story = { args: { priority: 'medium' } };
export const High: Story = { args: { priority: 'high' } };
export const Urgent: Story = { args: { priority: 'urgent' } };

export const AllPriorities: Story = {
  render: () => (
    <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap' }}>
      {PRIORITIES.map((priority) => (
        <PriorityChip key={priority} priority={priority} />
      ))}
    </Stack>
  ),
};
