import type { Meta, StoryObj } from '@storybook/react-vite';
import { AppHeader } from './AppHeader';
import { withRouter } from '../../../test/story-decorators';
import { mockUser } from '../../../test/factories';

const meta = {
  title: 'Organisms/AppHeader',
  component: AppHeader,
  decorators: [withRouter],
  args: { user: mockUser('agent'), onLogout: () => undefined },
  parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof AppHeader>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Authenticated: Story = { args: { user: mockUser('agent') } };
export const AsAdmin: Story = { args: { user: mockUser('admin') } };
export const Anonymous: Story = { args: { user: null } };
