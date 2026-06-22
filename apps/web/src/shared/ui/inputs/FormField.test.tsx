import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ColorModeProvider } from '../../theme/ColorModeProvider';
import { FormField } from './FormField';

describe('FormField', () => {
  it('asocia la etiqueta y muestra el mensaje de error', () => {
    render(
      <ColorModeProvider>
        <FormField
          name="title"
          label="Título"
          value=""
          onChange={() => undefined}
          error="El título es obligatorio."
        />
      </ColorModeProvider>,
    );
    expect(screen.getByLabelText(/Título/)).toBeInTheDocument();
    expect(screen.getByText('El título es obligatorio.')).toBeInTheDocument();
  });
});
