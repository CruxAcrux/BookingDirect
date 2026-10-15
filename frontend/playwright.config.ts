import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end tests run against the running development stack with seeded data:
 * `docker compose exec backend php artisan migrate:fresh --seed`, then
 * `docker compose run --rm e2e`.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  // The projects book against the same shop; running them together would race for the same time.
  workers: 1,
  retries: 0,
  reporter: [['list']],
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:5173',
    locale: 'sv-SE',
    timezoneId: 'Europe/Stockholm',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'mobile', use: { ...devices['Pixel 7'] } },
    { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
  ],
})
