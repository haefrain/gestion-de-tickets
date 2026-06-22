// Placeholder de administración (ruta solo-admin). La gestión real de usuarios llega en F6.
import Alert from '@mui/material/Alert';
import { PageContainer } from '../../shared/ui/layout/PageContainer';

export function AdminView() {
  return (
    <PageContainer title="Administración">
      <Alert severity="info">
        Panel de administración — disponible en F6 (gestión de usuarios y roles, HU de L1/L7).
      </Alert>
    </PageContainer>
  );
}
