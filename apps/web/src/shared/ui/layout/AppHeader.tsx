// Cabecera reutilizable (AppBar): marca + navegación por rol + toggle de tema + menú de usuario.
import AppBar from '@mui/material/AppBar';
import Toolbar from '@mui/material/Toolbar';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import ConfirmationNumberIcon from '@mui/icons-material/ConfirmationNumber';
import type { User } from '../../api/types';
import { NavMenu } from './NavMenu';
import { UserMenu } from './UserMenu';
import { ColorModeToggle } from './ColorModeToggle';

export interface AppHeaderProps {
  user: User | null;
  onLogout: () => void;
}

export function AppHeader({ user, onLogout }: AppHeaderProps) {
  return (
    <AppBar position="sticky" color="primary" elevation={1}>
      <Toolbar sx={{ gap: 2 }}>
        <ConfirmationNumberIcon aria-hidden />
        <Typography variant="h6" component="span" sx={{ fontWeight: 700, letterSpacing: 0.5 }}>
          Tickets
        </Typography>
        {user !== null ? <NavMenu role={user.role} /> : null}
        <Box sx={{ flexGrow: 1 }} />
        <ColorModeToggle />
        {user !== null ? <UserMenu user={user} onLogout={onLogout} /> : null}
      </Toolbar>
    </AppBar>
  );
}
