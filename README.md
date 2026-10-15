# BookingDirect

A multi-tenant booking system for barbershops: every shop gets its own booking page where customers pick a barber, a service and a time, while staff run the calendar and a live walk-in queue.

## Features

- Public booking page per shop (`/s/{shop-slug}`), built mobile-first
- Booking flow: service → barber (or any available) → time → phone verification
- Dashboard calendar for owners and barbers
- Live walk-in queue with estimated wait times
- Reminders by SMS and email, including smart rebooking reminders
- Waitlist that offers freed-up slots to the next customer
- Swedish and English throughout

## Tech stack

| Layer    | Technology                                                                     |
| -------- | ------------------------------------------------------------------------------ |
| Backend  | PHP 8.4, Laravel 13, Laravel Sanctum (cookie-based SPA auth), REST API         |
| Database | PostgreSQL 18                                                                  |
| Queue    | Redis                                                                          |
| Frontend | Vue 3, TypeScript, Vite, Vue Router, Pinia, TanStack Query, vue-i18n, Tailwind |
| Testing  | Pest, Vitest, Vue Test Utils                                                   |
| Quality  | Laravel Pint, Larastan, ESLint, Prettier, vue-tsc                              |
| Tooling  | Docker Compose, GitHub Actions                                                 |

## Getting started

**Prerequisites:** Docker with Docker Compose.

```bash
cd BookingDirect
docker compose up -d
docker compose exec backend php artisan migrate --seed
```

The first start installs dependencies and creates `backend/.env` from `backend/.env.example`, so it takes a minute.

| Service                     | URL                                 |
| --------------------------- | ----------------------------------- |
| App                         | http://localhost:5173               |
| Demo shop booking page      | http://localhost:5173/s/fade-studio |
| API                         | http://localhost:8000               |
| API documentation (OpenAPI) | http://localhost:8000/docs/api      |
| Mailpit (development email) | http://localhost:8025               |

If one of these ports is already taken, copy `.env.example` in the project root to `.env` and change it there.

### Sign-in accounts

The seeder creates these local accounts. The password is `password` for all of them.

| Role           | Email                     |
| -------------- | ------------------------- |
| Platform admin | admin@bookingdirect.test  |
| Shop owner     | owner@bookingdirect.test  |
| Barber         | barber@bookingdirect.test |

Staff sign in at http://localhost:5173/login.

### Booking as a customer

Customers book on the shop's page and confirm their phone number with a one-time SMS code. No SMS provider is connected in development, so the code is shown on screen as a demo code (set `OTP_DEMO_MODE=false` in `backend/.env` to turn this off).

## Running tests and checks

```bash
# Backend
docker compose exec backend ./vendor/bin/pest
docker compose exec backend ./vendor/bin/pint --test
docker compose exec backend ./vendor/bin/phpstan analyse

# Frontend
docker compose exec frontend npm test
docker compose exec frontend npm run lint
docker compose exec frontend npm run typecheck

# End-to-end (books a real appointment against the running stack with seeded data)
docker compose run --rm e2e
```

The frontend's API types are generated from the backend's OpenAPI spec. After changing an endpoint, regenerate them with `docker compose exec frontend npm run api:types`.

Backend tests run against a separate `bookingdirect_test` database, so your local data stays untouched.

## Project structure

```
backend/     Laravel API (routes under /api/v1)
frontend/    Vue single-page app, organised by feature in src/features
docker/      Dockerfiles and nginx/PostgreSQL configuration
.github/     CI workflow
```
