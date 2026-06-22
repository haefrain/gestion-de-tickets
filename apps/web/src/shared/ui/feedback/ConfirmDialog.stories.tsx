import type { Meta, StoryObj } from '@storybook/react-vite';
import { ConfirmDialog } from './ConfirmDialog';

const meta = {
  title: 'Feedback/ConfirmDialog',
  component: ConfirmDialog,
  args: {
    open: true,
    title: 'Resolver ticket',
    message: '¿Marcar este ticket como resuelto?',
    onConfirm: () => undefined,
    onCancel: () => undefined,
  },
} satisfies Meta<typeof ConfirmDialog>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};
export const Destructive: Story = {
  args: {
    title: 'Eliminar ticket',
    message: 'Esta acción no se puede deshacer. ¿Continuar?',
    confirmLabel: 'Eliminar',
    destructive: true,
  },
};
