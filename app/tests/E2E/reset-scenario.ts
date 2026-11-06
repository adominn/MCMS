import { execSync } from 'node:child_process';

// The scenario starts from the seed data of PRD 7.2 in its own database, so that it never meets the data of the other flows.
export default async function globalSetup() {
  const env = { ...process.env, DB_DATABASE: 'mcms_scenario', MCMS_STUB_IDP: 'true', MCMS_DEMO_CLOCK: 'true' };
  try {
    execSync('php artisan db:create-if-missing', { stdio: 'ignore', env });
  } catch {
    /* the database is created below when the command does not exist */
  }
  execSync('php artisan migrate:fresh --seed --force --no-interaction', { stdio: 'inherit', env });
  execSync('php artisan db:seed --class=DemoScenarioSeeder --force --no-interaction', { stdio: 'inherit', env });
}
