import { defineConfig } from '@playwright/test';

// FL-19: the Release 1 demonstration scenario of PRD Section 7, run act by act in its own database, with the demo clock on.
const env = { ...process.env, DB_DATABASE: 'mcms_scenario', MCMS_STUB_IDP: 'true', MCMS_DEMO_CLOCK: 'true', APP_ENV: 'local' } as Record<string, string>;
const port = 8102;

export default defineConfig({
  testDir: 'tests/E2E',
  testMatch: /scenario\.spec\.ts/,
  workers: 1,
  timeout: 240_000,
  reporter: 'list',
  globalSetup: './tests/E2E/reset-scenario.ts',
  use: { baseURL: `http://127.0.0.1:${port}`, channel: 'msedge', headless: true },
  webServer: { command: `php artisan serve --host=127.0.0.1 --port=${port}`, url: `http://127.0.0.1:${port}/up`, env, reuseExistingServer: false, timeout: 60_000 },
});
