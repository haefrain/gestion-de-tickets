import type { Meta, StoryObj } from '@storybook/react-vite';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

const VARIANTS = [
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'subtitle1',
  'subtitle2',
  'body1',
  'body2',
  'caption',
  'overline',
] as const;

function Scale() {
  return (
    <Stack spacing={1}>
      {VARIANTS.map((variant) => (
        <Typography key={variant} variant={variant}>
          {variant} · Gestión de Tickets
        </Typography>
      ))}
    </Stack>
  );
}

const meta = { title: 'Foundations/Typography' } satisfies Meta;
export default meta;
type Story = StoryObj;

export const Scale_: Story = { name: 'Escala tipográfica', render: () => <Scale /> };
