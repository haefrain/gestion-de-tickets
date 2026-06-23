// Perfil propio (HU-L1-E3-01): ver email y editar nombre y/o contraseña.
import { useState, type FormEvent } from 'react';
import Box from '@mui/material/Box';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import { PageContainer } from '../../shared/ui/layout/PageContainer';
import { DataState } from '../../shared/ui/feedback/DataState';
import { FormField } from '../../shared/ui/inputs/FormField';
import { useNotify } from '../../shared/ui/feedback/notifications-context';
import { useProfile, useUpdateProfile } from './api';

export function ProfileView() {
  const { data: profile, isLoading, isError } = useProfile();

  return (
    <PageContainer title="Mi perfil">
      <DataState loading={isLoading} error={isError ? 'No se pudo cargar el perfil.' : null}>
        {profile ? (
          // key: reinicia el formulario con los datos del servidor tras cada carga.
          <ProfileForm key={profile.id} email={profile.email} initialName={profile.name ?? ''} />
        ) : null}
      </DataState>
    </PageContainer>
  );
}

function ProfileForm({ email, initialName }: { email: string; initialName: string }) {
  const update = useUpdateProfile();
  const { notify } = useNotify();
  const [name, setName] = useState(initialName);
  const [password, setPassword] = useState('');

  function handleSubmit(event: FormEvent): void {
    event.preventDefault();
    const input: { name?: string; password?: string } = { name };
    if (password.trim() !== '') {
      input.password = password;
    }
    update.mutate(input, {
      onSuccess: () => {
        setPassword('');
        notify('Perfil actualizado.', 'success');
      },
      onError: () => notify('No se pudo actualizar el perfil.', 'error'),
    });
  }

  return (
    <Paper variant="outlined" sx={{ p: 3, maxWidth: 520 }}>
      <Stack spacing={1}>
        <Box>
          <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
            Email
          </Typography>
          <Typography variant="body1">{email}</Typography>
        </Box>
        <Box component="form" onSubmit={handleSubmit}>
          <FormField name="name" label="Nombre" value={name} onChange={setName} />
          <FormField
            name="password"
            label="Nueva contraseña (opcional)"
            type="password"
            value={password}
            onChange={setPassword}
          />
          <Button type="submit" variant="contained" sx={{ mt: 1 }} disabled={update.isPending}>
            Guardar cambios
          </Button>
        </Box>
      </Stack>
    </Paper>
  );
}
