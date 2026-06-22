import type { Meta, StoryObj } from '@storybook/react-vite';
import Typography from '@mui/material/Typography';
import { AppLayout } from './AppLayout';
import { PageContainer } from './PageContainer';
import { withRouter } from '../../../test/story-decorators';
import { mockUser } from '../../../test/factories';

const demoBody = (
  <PageContainer title="Tickets">
    <Typography>Contenido de ejemplo dentro del shell reutilizable (Header + cuerpo + Footer).</Typography>
  </PageContainer>
);

const meta = {
  title: 'Organisms/AppLayout',
  component: AppLayout,
  decorators: [withRouter],
  args: { user: mockUser('client'), onLogout: () => undefined, children: demoBody },
  parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof AppLayout>;

export default meta;
type Story = StoryObj<typeof meta>;

export const AsClient: Story = { args: { user: mockUser('client') } };
export const AsAgent: Story = { args: { user: mockUser('agent') } };
export const AsAdmin: Story = { args: { user: mockUser('admin') } };
export const DarkMode: Story = { args: { user: mockUser('agent') }, globals: { theme: 'dark' } };
