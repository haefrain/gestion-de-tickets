import type { Meta, StoryObj } from '@storybook/react-vite';
import { CommentList } from './CommentList';
import { mockComment } from '../../../test/factories';

const meta = {
  title: 'Organisms/CommentList',
  component: CommentList,
} satisfies Meta<typeof CommentList>;

export default meta;
type Story = StoryObj<typeof meta>;

export const WithComments: Story = {
  args: {
    comments: [
      mockComment({ authorName: 'Cliente Demo', body: 'Sigo sin poder entrar.' }),
      mockComment({
        authorName: 'Agente Demo',
        body: 'Estamos revisando el problema, gracias por reportarlo.',
      }),
    ],
  },
};
export const Empty: Story = { args: { comments: [] } };
