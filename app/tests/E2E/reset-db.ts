import { execSync } from 'node:child_process';

// Every run starts from the seed data (PRD 7.2) in its own database.
export default async function globalSetup() {
  execSync('php artisan migrate:fresh --seed --force --no-interaction', {
    stdio: 'inherit',
    env: { ...process.env, DB_DATABASE: 'mcms_e2e', MCMS_STUB_IDP: 'true' },
  });
}
