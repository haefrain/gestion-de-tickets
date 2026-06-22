import type { Meta, StoryObj } from '@storybook/react-vite';
import Box from '@mui/material/Box';
import { TicketForm } from './TicketForm';

const meta = {
  title: 'Organisms/TicketForm',
  component: TicketForm,
  args: { mode: 'create', onSubmit: () => undefined },
  decorators: [
    (Story) => (
      <Box sx={{ maxWidth: 480 }}>
        <Story />
      </Box>
    ),
  ],
} satisfies Meta<typeof TicketForm>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Create: Story = {};
export const Edit: Story = {
  args: {
    mode: 'edit',
    initialValues: {
      title: 'No puedo iniciar sesión',
      description: 'Error 500 al entrar.',
      priority: 'high',
      category: 'auth',
    },
  },
};
export const Submitting: Story = { args: { submitting: true } };
export const WithValidationErrors: Story = {
  args: { errors: { title: 'El título es obligatorio.', category: 'La categoría es obligatoria.' } },
};
