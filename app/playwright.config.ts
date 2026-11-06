import { defineConfig } from '@playwright/test';

// End-to-end flows of the UI/UX Design Specification (FL-xx) against the real application and its own database.
const env = { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true', APP_ENV: 'local' } as Record<string, string>;
const port = 8101;

export default defineConfig({
  testDir: 'tests/E2E',
  testIgnore: /scenario\.spec\.ts/,
  workers: 1,
  reporter: 'list',
  globalSetup: './tests/E2E/reset-db.ts',
  use: { baseURL: `http://127.0.0.1:${port}`, channel: 'msedge', headless: true },
  webServer: { command: `php artisan serve --host=127.0.0.1 --port=${port}`, url: `http://127.0.0.1:${port}/up`, env, reuseExistingServer: false, timeout: 60_000 },
});

