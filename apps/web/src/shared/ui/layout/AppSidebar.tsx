// Menú lateral (rail de navegación). Marca arriba, navegación por rol en el centro y
// la cuenta del usuario abajo. Prop-driven y reutilizable; el estado activo se anima.
import { NavLink } from 'react-router-dom';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Avatar from '@mui/material/Avatar';
import Divider from '@mui/material/Divider';
import Tooltip from '@mui/material/Tooltip';
import IconButton from '@mui/material/IconButton';
import ConfirmationNumberIcon from '@mui/icons-material/ConfirmationNumber';
import AdminPanelSettingsIcon from '@mui/icons-material/AdminPanelSettings';
import LogoutIcon from '@mui/icons-material/Logout';
import type { SvgIconComponent } from '@mui/icons-material';
import type { User, Role } from '../../api/types';
import { ROLE_LABEL } from '../../theme/tokens';
import { ROUTES } from '../../../app/router/routes';
import { ColorModeToggle } from './ColorModeToggle';

interface NavItem {
  label: string;
  to: string;
  icon: SvgIconComponent;
  roles?: Role[];
}

const NAV_ITEMS: NavItem[] = [
  { label: 'Tickets', to: ROUTES.tickets, icon: ConfirmationNumberIcon },
  { label: 'Administración', to: '/admin', icon: AdminPanelSettingsIcon, roles: ['admin'] },
];

export const SIDEBAR_WIDTH = 264;

function initialsOf(name: string): string {
  return name
    .split(' ')
    .map((part) => part[0] ?? '')
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

export interface AppSidebarProps {
  user: User;
  onLogout: () => void;
}

export function AppSidebar({ user, onLogout }: AppSidebarProps) {
  const items = NAV_ITEMS.filter((item) => item.roles === undefined || item.roles.includes(user.role));

  return (
    <Box
      component="aside"
      aria-label="Menú lateral"
      sx={{
        width: SIDEBAR_WIDTH,
        flexShrink: 0,
        position: 'sticky',
        top: 0,
        alignSelf: 'flex-start',
        height: '100vh',
        display: 'flex',
        flexDirection: 'column',
        bgcolor: 'background.paper',
        borderRight: 1,
        borderColor: 'divider',
      }}
    >
      {/* Marca */}
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, px: 3, height: 72, flexShrink: 0 }}>
        <Box
          sx={{
            width: 36,
            height: 36,
            borderRadius: 2,
            display: 'grid',
            placeItems: 'center',
            bgcolor: 'primary.main',
            color: 'primary.contrastText',
          }}
        >
          <ConfirmationNumberIcon fontSize="small" aria-hidden />
        </Box>
        <Box>
          <Typography variant="subtitle1" sx={{ fontWeight: 700, lineHeight: 1.1 }}>
            Tickets
          </Typography>
          <Typography variant="caption" color="text.secondary">
            IATSAE
          </Typography>
        </Box>
      </Box>

      <Divider />

      {/* Navegación */}
      <Box
        component="nav"
        aria-label="Navegación principal"
        sx={{ display: 'flex', flexDirection: 'column', gap: 0.5, p: 2, flexGrow: 1 }}
      >
        {items.map(({ label, to, icon: Icon }) => (
          <Box
            key={to}
            component={NavLink}
            to={to}
            end={to === ROUTES.tickets}
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 1.5,
              px: 2,
              py: 1.25,
              borderRadius: 2,
              color: 'text.secondary',
              textDecoration: 'none',
              fontSize: 14,
              fontWeight: 600,
              transition: 'background-color .18s ease, color .18s ease, transform .18s ease',
              '&:hover': { bgcolor: 'action.hover', color: 'text.primary', transform: 'translateX(2px)' },
              '&.active': { bgcolor: 'primary.main', color: 'primary.contrastText' },
              '&.active:hover': { bgcolor: 'primary.dark', color: 'primary.contrastText' },
            }}
          >
            <Icon fontSize="small" aria-hidden />
            {label}
          </Box>
        ))}
      </Box>

      <Divider />

      {/* Cuenta */}
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, p: 2 }}>
        <Avatar sx={{ width: 38, height: 38, bgcolor: 'secondary.main', fontSize: 15 }}>
          {initialsOf(user.name)}
        </Avatar>
        <Box sx={{ minWidth: 0, flexGrow: 1 }}>
          <Typography variant="subtitle2" noWrap>
            {user.name}
          </Typography>
          <Typography variant="caption" color="text.secondary" noWrap>
            {ROLE_LABEL[user.role]}
          </Typography>
        </Box>
        <ColorModeToggle />
        <Tooltip title="Cerrar sesión">
          <IconButton onClick={onLogout} aria-label="Cerrar sesión" size="small">
            <LogoutIcon fontSize="small" />
          </IconButton>
        </Tooltip>
      </Box>
    </Box>
  );
}
