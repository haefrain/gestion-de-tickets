import type { Meta, StoryObj } from '@storybook/react-vite';
import { useTheme } from '@mui/material/styles';
import Box from '@mui/material/Box';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

function SpacingScale() {
  const theme = useTheme();
  return (
    <Stack spacing={1.5}>
      {[1, 2, 3, 4, 6, 8].map((step) => (
        <Stack key={step} direction="row" spacing={1} sx={{ alignItems: 'center' }}>
          <Box sx={{ width: theme.spacing(step), height: 16, bgcolor: 'primary.main', borderRadius: 0.5 }} />
          <Typography variant="caption">
            spacing({step}) = {theme.spacing(step)}
          </Typography>
        </Stack>
      ))}
    </Stack>
  );
}

const meta = { title: 'Foundations/Spacing' } satisfies Meta;
export default meta;
type Story = StoryObj;

export const Scale_: Story = { name: 'Múltiplos de 8 px', render: () => <SpacingScale /> };
