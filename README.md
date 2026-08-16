<h1 align="center">🎬 CineFlow Booking Engine</h1>

<p align="center">
  <strong>A complete cinema booking system with real-time seat locking (concurrency) and dynamic pricing logic.</strong>
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
  <img alt="Stripe" src="https://img.shields.io/badge/Stripe-Checkout-635BFF?logo=stripe&logoColor=white">
  <img alt="Tests" src="https://img.shields.io/badge/tests-38%20passing-brightgreen">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-blue">
</p>

---

## What is this?

CineFlow is a production-grade cinema booking engine built with **Laravel 12**. It solves the hardest problem in any ticketing system: **stopping two people from buying the same seat at the same time**. It does this by combining real-time temporary seat locks with pessimistic database-level locking inside atomic transactions.

> Live demo: _(pending deployment — see [Deployment](#deployment))_

## Key features

- 🔒 **Real-time seat locking (concurrency).** When a user selects a seat, a temporary `SeatLock` (8 minutes) is created, tied to their user/session. Other users see the seat as taken instantly via AJAX polling.
- ⚔️ **Double-booking prevention with pessimistic locking.** Purchase confirmation runs inside `DB::transaction(attempts: 3)` using `lockForUpdate()`, so two simultaneous purchases of the same seat get serialized and the second one fails cleanly instead of creating an overbooking.
- 💶 **Dynamic pricing.** The price is calculated from the session's `preu_base` by applying factors per ticket type: Adult ×1.00, Reduced ×0.80, Family ×0.82, Senior 65+ ×0.70.
- 💳 **Stripe Checkout payments (test mode) + simulated fallback.** If Stripe keys are configured, card payment uses Stripe's hosted Checkout page (test card `4242 4242 4242 4242`). Without keys, it falls back automatically to a simulated payment flow so the app can be tested without a Stripe account.
- 🎟️ **Tickets with signed QR codes (HMAC)** and a customer dashboard with booking history.
- 🗂️ **Zero-config to deploy.** Uses SQLite by default (no database server to provision); MySQL-compatible for dev/production.

## Tech stack

| Layer | Technology |
|------|-----------|
| Backend | PHP 8.4, Laravel 12, Eloquent |
| Payments | Stripe Checkout (`stripe/stripe-php`) |
| Frontend | Blade, Tailwind CSS, Vite, JavaScript (AJAX seat polling) |
| Database | SQLite (default) / MySQL |
| Infra | Multi-stage Docker, Render / Railway / Fly.io |
| Tests | PHPUnit (38 tests, including concurrency and race-condition tests) |

## Concurrency architecture

```
User A ──┐                          ┌── SeatLock (temporary, 8 min)
          ├─► /comprar/step2 ────────┤
User B ──┘   (seat selection)       └── AJAX polling /seat/status

Confirmation:
  DB::transaction(attempts: 3)
    └─► SELECT ... FOR UPDATE   (lockForUpdate)
          ├─ seat free  ──► creates Reserva + ReservaSeat, releases locks
          └─ seat taken ──► SeatAlreadyReservedException ──► back to step2
```

The result: **it's impossible to sell the same seat twice**, even under concurrent requests (covered by `ConcurrentPurchaseTest`).

## Quick start (Docker)

```bash
docker compose up --build
# App at http://localhost:8000
```

The container generates the app key, creates the SQLite database, and runs migrations and seeders automatically.

### Local install (without Docker)

```bash
composer install
npm ci && npm run build
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

### Test users (seeder)

| Role | Email | Password |
|-----|-------|-----------|
| Admin | `admin@cineflow.test` | `admin1234` |
| Box office | `taquilla@cineflow.test` | `taquilla1234` |
| Customer | `cliente@cineflow.test` | `cliente1234` |

## Configuring Stripe (optional)

Add to your `.env`:

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_CURRENCY=eur
```

Without these keys, card payment uses the simulated flow. With them, it uses real Stripe Checkout in test mode.

## Tests

```bash
php artisan test
```

Runs 38 tests against SQLite: full purchase flow, lock expiration, race-condition prevention, pessimistic locking, authentication and profile.

## Deployment

The repo includes ready-to-use blueprints for several platforms:

- **Render** → `render.yaml`
- **Railway** → `railway.json`
- **Fly.io** → `fly.toml`
- **Docker** → `Dockerfile` + `docker/entrypoint.sh`

All of them use SQLite by default (zero database configuration). For production with MySQL, set the `DB_*` variables on the platform.

## Credits

CineFlow is the portfolio evolution of a final-cycle project (module M0616) originally developed as a team:

- **Ayman Charoui**
- **Ismael Achamrouk**
- **Danna Guevara**

This repo repackages, modernizes (real Stripe, deployment, CI) and documents that work. The original technical documentation is preserved in [`docs/`](docs/).

## License

[MIT](LICENSE).
</content>
