// Botón de tema claro/oscuro. Consume el ColorModeProvider (única fuente del tema).
import IconButton from '@mui/material/IconButton';
import Tooltip from '@mui/material/Tooltip';
import LightModeIcon from '@mui/icons-material/LightMode';
import DarkModeIcon from '@mui/icons-material/DarkMode';
import { useColorMode } from '../../theme/color-mode-context';

export function ColorModeToggle() {
  const { mode, toggle } = useColorMode();
  const isDark = mode === 'dark';
  const label = isDark ? 'Activar tema claro' : 'Activar tema oscuro';

  return (
    <Tooltip title={label}>
      <IconButton onClick={toggle} color="inherit" aria-label={label}>
        {isDark ? <LightModeIcon /> : <DarkModeIcon />}
      </IconButton>
    </Tooltip>
  );
}
