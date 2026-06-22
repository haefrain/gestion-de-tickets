// Vista de registro. Crea la cuenta vía apiClient y entra (estructura lista; backend en F6).
import { useState, type FormEvent } from 'react';
import { useNavigate, Link as RouterLink } from 'react-router-dom';
import Container from '@mui/material/Container';
import Paper from '@mui/material/Paper';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Link from '@mui/material/Link';
import { FormField } from '../../shared/ui/inputs/FormField';
import { ApiError } from '../../shared/api/ProblemDetails';
import { ROUTES } from '../../app/router/routes';
import { useAuth } from './auth-context';

export function RegisterView() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent): Promise<void> {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      await register({ name, email, password });
      navigate(ROUTES.tickets, { replace: true });
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors(caught.fieldErrors());
        setError(caught.message);
      } else {
        setError('No se pudo registrar. El backend de identidad llega en F6 (L1).');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Container maxWidth="sm" sx={{ py: 8 }}>
      <Paper variant="outlined" sx={{ p: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          Crear cuenta
        </Typography>
        {error !== null ? (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        ) : null}
        <Box component="form" onSubmit={handleSubmit} noValidate>
          <FormField
            name="name"
            label="Nombre"
            value={name}
            onChange={setName}
            error={fieldErrors.name}
            required
            autoFocus
            disabled={submitting}
          />
          <FormField
            name="email"
            label="Email"
            type="email"
            value={email}
            onChange={setEmail}
            error={fieldErrors.email}
            required
            disabled={submitting}
          />
          <FormField
            name="password"
            label="Contraseña"
            type="password"
            value={password}
            onChange={setPassword}
            error={fieldErrors.password}
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
            Registrarme
          </Button>
        </Box>
        <Typography variant="body2" sx={{ mt: 2 }}>
          ¿Ya tienes cuenta?{' '}
          <Link component={RouterLink} to={ROUTES.login}>
            Inicia sesión
          </Link>
        </Typography>
      </Paper>
    </Container>
  );
}
