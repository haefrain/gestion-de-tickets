import { defineConfig, devices } from '@playwright/test';

// E2E del flujo principal (docs/quality/testing.md). Requiere el dev server arriba
// y los navegadores instalados (`npx playwright install chromium`).
export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  reporter: 'list',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:5173',
    trace: 'on-first-retry',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
