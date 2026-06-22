import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { FormField, type FormFieldProps } from './FormField';

// Wrapper interactivo: FormField es controlado, así la story permite escribir.
function FieldDemo({
  initial = '',
  ...props
}: Omit<FormFieldProps, 'value' | 'onChange'> & { initial?: string }) {
  const [value, setValue] = useState(initial);
  return <FormField {...props} value={value} onChange={setValue} />;
}

const meta = {
  title: 'Molecules/FormField',
  component: FormField,
  args: { name: 'title', label: 'Título', value: '', onChange: () => undefined },
} satisfies Meta<typeof FormField>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = { render: () => <FieldDemo name="title" label="Título" /> };
export const Required: Story = { render: () => <FieldDemo name="title" label="Título" required /> };
export const WithError: Story = {
  render: () => <FieldDemo name="title" label="Título" initial="x" error="El título es obligatorio." />,
};
export const Disabled: Story = {
  render: () => <FieldDemo name="title" label="Título" initial="No editable" disabled />,
};
