import { test, expect } from '@playwright/test';

test('la pantalla de login carga', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: 'Iniciar sesión' })).toBeVisible();
});

test('el login con credenciales válidas entra al listado de tickets', async ({ page }) => {
  // Backend simulado: el job E2E corre sobre el build (vite preview), sin el stack Docker.
  await page.route('**/api/v1/token/refresh', (route) =>
    route.fulfill({ status: 401, contentType: 'application/json', body: '{}' }),
  );
  await page.route('**/api/v1/login', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ access_token: 'e2e-token', expires_in: 900 }),
    }),
  );
  await page.route('**/api/v1/me', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: '019ef235-0000-7000-8000-000000000000',
        email: 'agente@demo.local',
        name: 'Agente Demo',
        roles: ['ROLE_AGENT'],
      }),
    }),
  );
  const emptyPage = JSON.stringify({
    data: [],
    page: { limit: 20, next_cursor: null, has_more: false },
  });
  await page.route('**/api/v1/tickets**', (route) =>
    route.fulfill({ status: 200, contentType: 'application/json', body: emptyPage }),
  );
  await page.route('**/api/v1/search/**', (route) =>
    route.fulfill({ status: 200, contentType: 'application/json', body: emptyPage }),
  );

  await page.goto('/login');
  await page.getByLabel('Email').fill('agente@demo.local');
  await page.getByLabel('Contraseña').fill('Demo1234');
  await page.getByRole('button', { name: 'Entrar' }).click();

  await expect(page.getByRole('heading', { name: 'Tickets', level: 1 })).toBeVisible();
});
