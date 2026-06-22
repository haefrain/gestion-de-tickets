import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ColorModeProvider } from '../../theme/ColorModeProvider';
import { TicketForm } from './TicketForm';

describe('TicketForm', () => {
  it('no envía y muestra errores si faltan campos obligatorios', async () => {
    const onSubmit = vi.fn();
    render(
      <ColorModeProvider>
        <TicketForm mode="create" onSubmit={onSubmit} />
      </ColorModeProvider>,
    );

    await userEvent.click(screen.getByRole('button', { name: 'Crear ticket' }));

    expect(onSubmit).not.toHaveBeenCalled();
    expect(screen.getByText('El título es obligatorio.')).toBeInTheDocument();
  });

  it('envía los valores cuando el formulario es válido', async () => {
    const onSubmit = vi.fn();
    render(
      <ColorModeProvider>
        <TicketForm mode="create" onSubmit={onSubmit} />
      </ColorModeProvider>,
    );

    await userEvent.type(screen.getByLabelText(/Título/), 'No puedo entrar');
    await userEvent.type(screen.getByLabelText(/Categoría/), 'auth');
    await userEvent.click(screen.getByRole('button', { name: 'Crear ticket' }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
  });
});
