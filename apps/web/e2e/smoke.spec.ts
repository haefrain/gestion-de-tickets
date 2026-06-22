import { test, expect } from '@playwright/test';

test('la pantalla de login carga', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: 'Iniciar sesión' })).toBeVisible();
});

test('el acceso de demostración entra al listado de tickets', async ({ page }) => {
  await page.goto('/login');
  await page.getByRole('button', { name: 'Demo Agente' }).click();
  await expect(page.getByRole('heading', { name: 'Tickets', level: 1 })).toBeVisible();
});
