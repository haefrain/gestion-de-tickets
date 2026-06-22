import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from '../features/auth/AuthProvider';
import type { User } from '../shared/api/types';
import { RequireAuth } from './RequireAuth';

function renderAt(initialUser: User | null) {
  return render(
    <AuthProvider initialUser={initialUser}>
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route
            path="/"
            element={
              <RequireAuth>
                <div>Contenido privado</div>
              </RequireAuth>
            }
          />
          <Route path="/login" element={<div>Pantalla de login</div>} />
        </Routes>
      </MemoryRouter>
    </AuthProvider>,
  );
}

describe('RequireAuth', () => {
  it('redirige a login cuando no hay sesión', () => {
    renderAt(null);
    expect(screen.getByText('Pantalla de login')).toBeInTheDocument();
  });

  it('muestra el contenido cuando hay sesión', () => {
    renderAt({ id: '1', name: 'Agente Demo', email: 'a@t.local', role: 'agent' });
    expect(screen.getByText('Contenido privado')).toBeInTheDocument();
  });
});
