// Cuerpo de página reutilizable: ancho máximo, padding y cabecera opcional (título + acciones).
import type { ReactNode } from 'react';
import Container from '@mui/material/Container';
import Box from '@mui/material/Box';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

export interface PageContainerProps {
  title?: string;
  actions?: ReactNode;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | false;
  children: ReactNode;
}

export function PageContainer({ title, actions, maxWidth = 'lg', children }: PageContainerProps) {
  const hasHeader = title !== undefined || actions !== undefined;

  return (
    <Container maxWidth={maxWidth} sx={{ py: 3, width: '100%' }}>
      {hasHeader ? (
        <Stack
          direction="row"
          spacing={2}
          sx={{ justifyContent: 'space-between', alignItems: 'center', mb: 3 }}
        >
          {title !== undefined ? (
            <Typography variant="h4" component="h1">
              {title}
            </Typography>
          ) : (
            <Box />
          )}
          {actions}
        </Stack>
      ) : null}
      {children}
    </Container>
  );
}
