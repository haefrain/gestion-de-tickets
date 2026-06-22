import type { Meta, StoryObj } from '@storybook/react-vite';
import { CursorPagination } from './CursorPagination';

const meta = {
  title: 'Molecules/CursorPagination',
  component: CursorPagination,
  args: { hasMore: true, hasPrev: false, loading: false, onNext: () => undefined, onPrev: () => undefined },
} satisfies Meta<typeof CursorPagination>;

export default meta;
type Story = StoryObj<typeof meta>;

export const FirstPage: Story = { args: { hasMore: true, hasPrev: false } };
export const Middle: Story = { args: { hasMore: true, hasPrev: true } };
export const LastPage: Story = { args: { hasMore: false, hasPrev: true } };
export const Loading: Story = { args: { hasMore: true, hasPrev: true, loading: true } };
