// Decoradores reutilizables para stories que necesitan contexto de router.
import type { Decorator } from '@storybook/react-vite';
import { MemoryRouter } from 'react-router-dom';

export const withRouter: Decorator = (Story) => (
  <MemoryRouter initialEntries={['/']}>
    <Story />
  </MemoryRouter>
);
