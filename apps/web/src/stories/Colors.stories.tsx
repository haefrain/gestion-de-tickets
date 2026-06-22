import type { Meta, StoryObj } from '@storybook/react-vite';
import { useTheme } from '@mui/material/styles';
import Box from '@mui/material/Box';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

function Swatch({ name, color, contrast }: { name: string; color: string; contrast: string }) {
  return (
    <Box sx={{ width: 150 }}>
      <Box
        sx={{
          bgcolor: color,
          color: contrast,
          height: 72,
          borderRadius: 1,
          display: 'flex',
          alignItems: 'flex-end',
          p: 1,
        }}
      >
        <Typography variant="caption">{name}</Typography>
      </Box>
      <Typography variant="caption" color="text.secondary">
        {color}
      </Typography>
    </Box>
  );
}

function PaletteSwatches() {
  const { palette } = useTheme();
  const items = [
    { name: 'primary (índigo)', main: palette.primary.main, contrast: palette.primary.contrastText },
    { name: 'secondary', main: palette.secondary.main, contrast: palette.secondary.contrastText },
    { name: 'success', main: palette.success.main, contrast: palette.success.contrastText },
    { name: 'warning', main: palette.warning.main, contrast: palette.warning.contrastText },
    { name: 'error', main: palette.error.main, contrast: palette.error.contrastText },
    { name: 'info', main: palette.info.main, contrast: palette.info.contrastText },
  ];
  return (
    <Stack direction="row" sx={{ flexWrap: 'wrap', gap: 2 }}>
      {items.map((item) => (
        <Swatch key={item.name} name={item.name} color={item.main} contrast={item.contrast} />
      ))}
    </Stack>
  );
}

const meta = { title: 'Foundations/Colors' } satisfies Meta;
export default meta;
type Story = StoryObj;

export const Palette: Story = { render: () => <PaletteSwatches /> };
