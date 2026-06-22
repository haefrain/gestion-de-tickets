// Navegación principal, según rol. Reutilizada por AppHeader. Usa NavLink (estado activo).
import Stack from '@mui/material/Stack';
import Button from '@mui/material/Button';
import { NavLink } from 'react-router-dom';
import type { Role } from '../../api/types';
import { ROUTES } from '../../../app/router/routes';

interface NavItem {
  label: string;
  to: string;
  roles?: Role[];
}

const NAV_ITEMS: NavItem[] = [
  { label: 'Tickets', to: ROUTES.tickets },
  { label: 'Administración', to: '/admin', roles: ['admin'] },
];

export interface NavMenuProps {
  role: Role;
}

export function NavMenu({ role }: NavMenuProps) {
  const items = NAV_ITEMS.filter((item) => item.roles === undefined || item.roles.includes(role));

  return (
    <Stack direction="row" spacing={0.5} component="nav" aria-label="Navegación principal">
      {items.map((item) => (
        <Button
          key={item.to}
          component={NavLink}
          to={item.to}
          end={item.to === ROUTES.tickets}
          color="inherit"
          sx={{ '&.active': { fontWeight: 700, textDecoration: 'underline' } }}
        >
          {item.label}
        </Button>
      ))}
    </Stack>
  );
}
