import type { Meta, StoryObj } from '@storybook/react-vite';
import Typography from '@mui/material/Typography';
import { DataState } from './DataState';

const meta = {
  title: 'Feedback/DataState',
  component: DataState,
  args: { children: <Typography>Contenido cargado correctamente.</Typography> },
} satisfies Meta<typeof DataState>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Loading: Story = { args: { loading: true } };
export const ErrorState: Story = { args: { error: 'No se pudieron cargar los datos.' } };
export const Empty: Story = { args: { empty: true, emptyMessage: 'No hay tickets que mostrar.' } };
export const WithContent: Story = {};
