// Vista de login: formulario real contra la API. En desarrollo, accesos rápidos por rol con las
// credenciales sembradas (make seed) para recorrer la demo sin teclear.
import { useState, type FormEvent } from 'react';
import { useNavigate, useLocation, Link as RouterLink } from 'react-router-dom';
import Container from '@mui/material/Container';
import Paper from '@mui/material/Paper';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Stack from '@mui/material/Stack';
import Divider from '@mui/material/Divider';
import Link from '@mui/material/Link';
import { FormField } from '../../shared/ui/inputs/FormField';
import { ApiError } from '../../shared/api/ProblemDetails';
import type { Role } from '../../shared/api/types';
import { ROUTES } from '../../app/router/routes';
import { useAuth } from './auth-context';

export function LoginView() {
  const { login, loginAsDemo } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const from = (location.state as { from?: string } | null)?.from ?? ROUTES.tickets;

  async function handleSubmit(event: FormEvent): Promise<void> {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await login(email, password);
      navigate(from, { replace: true });
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No se pudo iniciar sesión. Revisa tu conexión e inténtalo de nuevo.',
      );
    } finally {
      setSubmitting(false);
    }
  }

  async function enterDemo(role: Role): Promise<void> {
    setSubmitting(true);
    setError(null);
    try {
      await loginAsDemo(role);
      navigate(from, { replace: true });
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No se pudo entrar en modo demo. ¿Cargaste los datos con «make seed»?',
      );
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Container maxWidth="sm" sx={{ py: 8 }}>
      <Paper variant="outlined" sx={{ p: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          Iniciar sesión
        </Typography>
        {error !== null ? (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        ) : null}
        <Box component="form" onSubmit={handleSubmit} noValidate>
          <FormField
            name="email"
            label="Email"
            type="email"
            value={email}
            onChange={setEmail}
            required
            autoFocus
            disabled={submitting}
          />
          <FormField
            name="password"
            label="Contraseña"
            type="password"
            value={password}
            onChange={setPassword}
            required
            disabled={submitting}
          />
          <Button
            type="submit"
            variant="contained"
            fullWidth
            size="large"
            disabled={submitting}
            sx={{ mt: 2 }}
          >
            Entrar
          </Button>
        </Box>
        <Typography variant="body2" sx={{ mt: 2 }}>
          ¿No tienes cuenta?{' '}
          <Link component={RouterLink} to={ROUTES.register}>
            Regístrate
          </Link>
        </Typography>
        {import.meta.env.DEV ? (
          <>
            <Divider sx={{ my: 3 }}>o explora la demo</Divider>
            <Stack direction="row" spacing={1} sx={{ justifyContent: 'center', flexWrap: 'wrap' }}>
              <Button variant="outlined" disabled={submitting} onClick={() => void enterDemo('client')}>
                Demo Cliente
              </Button>
              <Button variant="outlined" disabled={submitting} onClick={() => void enterDemo('agent')}>
                Demo Agente
              </Button>
              <Button variant="outlined" disabled={submitting} onClick={() => void enterDemo('admin')}>
                Demo Admin
              </Button>
            </Stack>
          </>
        ) : null}
      </Paper>
    </Container>
  );
}
