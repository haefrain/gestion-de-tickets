import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { SearchBar, type SearchBarProps } from './SearchBar';

function SearchBarDemo({
  initial = '',
  ...props
}: Omit<SearchBarProps, 'value' | 'onChange'> & { initial?: string }) {
  const [value, setValue] = useState(initial);
  return <SearchBar {...props} value={value} onChange={setValue} />;
}

const meta = {
  title: 'Molecules/SearchBar',
  component: SearchBar,
  args: { value: '', onChange: () => undefined },
} satisfies Meta<typeof SearchBar>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = { render: () => <SearchBarDemo /> };
export const WithValue: Story = { render: () => <SearchBarDemo initial="login" /> };
export const Disabled: Story = { render: () => <SearchBarDemo initial="login" disabled /> };
