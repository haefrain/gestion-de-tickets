// Administración de usuarios (HU-L1-E2-03): cambiar rol y activar/desactivar. Ruta solo-admin.
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Paper from '@mui/material/Paper';
import Select, { type SelectChangeEvent } from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import Switch from '@mui/material/Switch';
import { PageContainer } from '../../shared/ui/layout/PageContainer';
import { DataState } from '../../shared/ui/feedback/DataState';
import { useNotify } from '../../shared/ui/feedback/notifications-context';
import { useUpdateUser, useUsers, type AdminUser } from './api';

const ROLE_OPTIONS = [
  { value: 'ROLE_CLIENT', label: 'Cliente' },
  { value: 'ROLE_AGENT', label: 'Agente' },
  { value: 'ROLE_ADMIN', label: 'Admin' },
] as const;

function primaryRole(roles: string[]): string {
  if (roles.includes('ROLE_ADMIN')) return 'ROLE_ADMIN';
  if (roles.includes('ROLE_AGENT')) return 'ROLE_AGENT';
  return 'ROLE_CLIENT';
}

export function AdminView() {
  const { data: users, isLoading, isError } = useUsers();
  const update = useUpdateUser();
  const { notify } = useNotify();
  const rows = users ?? [];

  function changeRole(user: AdminUser, event: SelectChangeEvent): void {
    update.mutate(
      { id: user.id, roles: [event.target.value] },
      {
        onSuccess: () => notify('Rol actualizado.', 'success'),
        onError: () => notify('No se pudo actualizar el rol.', 'error'),
      },
    );
  }

  function toggleActive(user: AdminUser, active: boolean): void {
    update.mutate(
      { id: user.id, active },
      {
        onSuccess: () => notify(active ? 'Cuenta activada.' : 'Cuenta desactivada.', 'success'),
        onError: () => notify('No se pudo cambiar el estado.', 'error'),
      },
    );
  }

  return (
    <PageContainer title="Administración de usuarios">
      <DataState
        loading={isLoading}
        error={isError ? 'No se pudieron cargar los usuarios.' : null}
        empty={rows.length === 0}
        emptyMessage="No hay usuarios."
      >
        <TableContainer component={Paper} variant="outlined">
          <Table aria-label="Usuarios">
            <TableHead>
              <TableRow>
                <TableCell>Email</TableCell>
                <TableCell>Nombre</TableCell>
                <TableCell>Rol</TableCell>
                <TableCell align="center">Activo</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {rows.map((user) => (
                <TableRow key={user.id} hover>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>{user.name ?? '—'}</TableCell>
                  <TableCell>
                    <Select
                      size="small"
                      value={primaryRole(user.roles)}
                      onChange={(event) => changeRole(user, event)}
                      disabled={update.isPending}
                      aria-label={`Rol de ${user.email}`}
                    >
                      {ROLE_OPTIONS.map((option) => (
                        <MenuItem key={option.value} value={option.value}>
                          {option.label}
                        </MenuItem>
                      ))}
                    </Select>
                  </TableCell>
                  <TableCell align="center">
                    <Switch
                      checked={user.active}
                      onChange={(event) => toggleActive(user, event.target.checked)}
                      disabled={update.isPending}
                      slotProps={{ input: { 'aria-label': `Activo: ${user.email}` } }}
                    />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </DataState>
    </PageContainer>
  );
}
