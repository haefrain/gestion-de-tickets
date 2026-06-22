import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ColorModeProvider } from '../../theme/ColorModeProvider';
import { StatusChip } from './StatusChip';

describe('StatusChip', () => {
  it('muestra la etiqueta del estado (no depende solo del color)', () => {
    render(
      <ColorModeProvider>
        <StatusChip status="in_progress" />
      </ColorModeProvider>,
    );
    expect(screen.getByText('En progreso')).toBeInTheDocument();
  });
});
