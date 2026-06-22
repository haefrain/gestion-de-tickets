import type { Meta, StoryObj } from '@storybook/react-vite';
import { TicketTable } from './TicketTable';
import { DataState } from '../feedback/DataState';
import { mockTickets } from '../../../test/factories';

const meta = {
  title: 'Organisms/TicketTable',
  component: TicketTable,
  args: { tickets: mockTickets(5) },
} satisfies Meta<typeof TicketTable>;

export default meta;
type Story = StoryObj<typeof meta>;

export const WithData: Story = {};

// Los estados se demuestran componiendo DataState (sin duplicar lógica en la tabla).
export const Loading: Story = {
  render: () => (
    <DataState loading>
      <TicketTable tickets={[]} />
    </DataState>
  ),
};
export const Empty: Story = {
  render: () => (
    <DataState empty emptyMessage="No hay tickets.">
      <TicketTable tickets={[]} />
    </DataState>
  ),
};
export const ErrorState: Story = {
  render: () => (
    <DataState error="No se pudieron cargar los tickets.">
      <TicketTable tickets={[]} />
    </DataState>
  ),
};
