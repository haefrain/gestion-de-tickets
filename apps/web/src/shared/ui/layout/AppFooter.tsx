// Pie reutilizable.
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Link from '@mui/material/Link';

export function AppFooter() {
  return (
    <Box
      component="footer"
      sx={{ py: 2, px: 3, mt: 'auto', borderTop: 1, borderColor: 'divider', textAlign: 'center' }}
    >
      <Typography variant="body2" color="text.secondary">
        Sistema de Gestión de Tickets · Prueba técnica IATSAE ·{' '}
        <Link
          href="https://github.com/haefrain/gestion-de-tickets"
          target="_blank"
          rel="noopener"
          color="inherit"
        >
          repositorio
        </Link>
      </Typography>
    </Box>
  );
}
