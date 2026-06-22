import type { Meta, StoryObj } from '@storybook/react-vite';
import Box from '@mui/material/Box';
import { TicketCard } from './TicketCard';
import { mockTicket } from '../../../test/factories';

const meta = {
  title: 'Organisms/TicketCard',
  component: TicketCard,
  args: { ticket: mockTicket() },
  decorators: [
    (Story) => (
      <Box sx={{ maxWidth: 420 }}>
        <Story />
      </Box>
    ),
  ],
} satisfies Meta<typeof TicketCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Open: Story = { args: { ticket: mockTicket({ status: 'open', priority: 'medium' }) } };
export const Urgent: Story = { args: { ticket: mockTicket({ status: 'in_progress', priority: 'urgent' }) } };
export const Assigned: Story = {
  args: { ticket: mockTicket({ assigneeId: 'demo-agent', assigneeName: 'Agente Demo' }) },
};
export const Unassigned: Story = { args: { ticket: mockTicket({ assigneeId: null, assigneeName: null }) } };
export const LongTitle: Story = {
  args: {
    ticket: mockTicket({
      title:
        'Un título realmente largo que debe ajustarse correctamente dentro de la tarjeta sin romper el layout ni desbordar el contenedor',
    }),
  },
};
