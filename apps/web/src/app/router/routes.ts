// Rutas de la app, centralizadas (nada de strings sueltos repetidos).
export const ROUTES = {
  login: '/login',
  register: '/register',
  tickets: '/',
  ticketDetailPattern: '/tickets/:id',
  ticketDetail: (id: string): string => `/tickets/${id}`,
  profile: '/profile',
  admin: '/admin',
} as const;
