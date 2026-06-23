// Rutas de la app. Las privadas comparten el AppLayout (un único shell) vía ruta de layout.
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import { AppLayout } from '../../shared/ui/layout/AppLayout';
import { LoginView } from '../../features/auth/LoginView';
import { RegisterView } from '../../features/auth/RegisterView';
import { TicketListView } from '../../features/tickets/TicketListView';
import { TicketDetailView } from '../../features/tickets/TicketDetailView';
import { ProfileView } from '../../features/profile/ProfileView';
import { AdminView } from '../../features/admin/AdminView';
import { useAuth } from '../../features/auth/auth-context';
import { RequireAuth } from '../RequireAuth';
import { ROUTES } from './routes';

function ProtectedLayout() {
  const { user, logout } = useAuth();
  return (
    <AppLayout user={user} onLogout={logout}>
      <Outlet />
    </AppLayout>
  );
}

export function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path={ROUTES.login} element={<LoginView />} />
        <Route path={ROUTES.register} element={<RegisterView />} />
        <Route
          element={
            <RequireAuth>
              <ProtectedLayout />
            </RequireAuth>
          }
        >
          <Route path={ROUTES.tickets} element={<TicketListView />} />
          <Route path={ROUTES.ticketDetailPattern} element={<TicketDetailView />} />
          <Route path={ROUTES.profile} element={<ProfileView />} />
          <Route
            path={ROUTES.admin}
            element={
              <RequireAuth roles={['admin']}>
                <AdminView />
              </RequireAuth>
            }
          />
        </Route>
        <Route path="*" element={<Navigate to={ROUTES.tickets} replace />} />
      </Routes>
    </BrowserRouter>
  );
}
