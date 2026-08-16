# CineFlow — Presentation Document

**DAW final project · M0613 + M0616**
**Team:** Ayman Charoui · Ismael Achamrouk · Danna Guevara

---

## 1. What we built

A **complete cinema ticket-sales system** in the style of Yelmo or Ocine, combining:

- A public listings page with real movies currently in Spanish theaters (auto-synced with TMDB).
- A multi-step purchase flow with real-time seat locking (prevents concurrent double-selling).
- An admin panel with roles (admin, taquilla, cliente, guest).
- A modular Laravel architecture with services, scheduled commands, versioned migrations, and tests.

---

## 2. Problem solved

In real cinemas, two customers can try to buy the same seat at the same time. Without concurrency control, one of them loses money or has their reservation cancelled. CineFlow implements **temporary seat locking** (`seat_locks`) with a short TTL: while a customer is paying, the seat stays reserved for them; if they abandon the process, it's released automatically.

On top of that, a real listings page needs to reflect what's **actually showing right now**. That's why we consume the TMDB API (`now_playing · region=ES`) every 6 hours and auto-generate sessions for the currently-showing movies.

---

## 3. Core business logic

### 3.1 Listings page

**Rule:** only movies with **at least one active future session** are shown.
- Implemented in `PeliculaController::index()` with `whereHas('sesiones', fn($q) => $q->where('fecha_hora', '>=', now()))`.
- Dynamic filters: day, time slot, cinema, category.

### 3.2 Automatic sync

`sesions:generate-from-releases` command:
1. Calls TMDB's `now_playing` with `region=ES`.
2. For each movie, creates it in the DB if it doesn't already exist.
3. For each sala, generates 7 sessions (one per day, at 8:00 PM) if they don't already exist.

Runs every 6 hours via `app/Console/Kernel.php`.

### 3.3 Purchase flow (3 steps)

```
Step 1 → select session + ticket quantity/types
Step 2 → select seats (real-time AJAX lock/unlock)
Step 3 → payment details + confirmation
```

During step 2, every click on a seat calls `/api/seat-lock`, creating a `SeatLock` with `expires_at = now + 10min`. On purchase confirmation, the locks are converted into definitive `ReservaSeat` records.

### 3.4 Automatic cleanup

- Every minute: `cleanup:seat-locks` removes locks with `expires_at < now`.
- Every day at 03:00: `sesions:clean` deletes past sessions (cascading cleanup of associated reservations and locks).

---

## 4. Key methods and services

### `App\Services\DevsApiHubMovieService`
HTTP client for TMDB. Public methods:
- `getNowPlaying()` — current listings in Spain.
- `searchByQuery(string)` — search for the admin widget.
- `getById(int)` — detail with age certification and runtime.
- `isEnabled()` — configuration check.

### `App\Services\SeatAvailabilityService`
Calculates available seats for a session given the sala's capacity, subtracting currently reserved + locked seats.

### `App\Services\PurchaseService`
Encapsulates validation of the transition between steps and final persistence of the reservation.

### `App\Services\GuestCheckoutService`
Allows purchases without prior registration, creating a user with the `guest` role using just an email.

### `App\Models\SeatLock`
Static methods:
- `clearExpired()` — purges expired locks.
- `isLockedByOther($sesionId, $butaca, $userId, $token)` — true if another customer holds the seat.

### `App\Http\Controllers\CompraController`
AJAX endpoints for the seat picker:
- `POST /api/seat-lock` — locks a seat.
- `POST /api/seat-unlock` — releases it.
- `GET /api/seats/{sesionId}` — current status of all seats for the session.

---

## 5. Architecture and technical decisions

### Why Laravel 12
- Expressive Eloquent ORM → complex relationships (pelicula → sesion → sala → cine) stay clean.
- Built-in scheduler → declarative cronjobs in `Kernel.php`, no touching the system crontab.
- Breeze for auth → roles with standard middleware.

### Why locking in the DB and not Redis
- Simplicity: the team knows MySQL, not Redis.
- Short TTL + cleanup every minute → table size stays bounded.
- Traceability: we can audit which user locked what.

### Why TMDB instead of manual data
- Real listings with no manual maintenance.
- Official posters, synopses, genres, ES certifications.
- Read-only → no risk of corrupting remote data.

### Critical indexes (migration `add_critical_indexes`)
- `reservas.fk_sesion_id` — looking up reserved seats by session.
- `seat_locks.(sesion_id, butaca)` — conflict detection when locking.
- `sesions.fecha_hora` — listings filters by date.

---

## 6. Security

- CSRF on every POST (Laravel middleware).
- Rate limiting on login (Breeze) + audit log with `LogThrottleAttempts`.
- Strict `FormRequest` validation for purchases and profile edits.
- Role management with `$user->isAdmin()`, `canManage()`, etc.
- Debug routes and leftover test files purged before deployment.

---

## 7. Responsive design

Three main breakpoints:
- **Desktop** (> 1024px) — filter sidebar + 4-5 col listings grid.
- **Tablet** (641–1024px) — filters collapse to the top, 3-col grid.
- **Mobile** (≤ 640px) — hamburger menu, 2-col grid, horizontally-scrolling tables.
- **Small mobile** (≤ 380px) — compact grid.

Implemented in `resources/css/app.css` and mirrored in `public/css/app.css`.

---

## 8. Testing

Feature tests included:
- `ConcurrentPurchaseTest` — two customers compete for the same seat; only one wins.
- `PurchaseFlowTest` — full flow step1 → step3 → confirmation.
- `ServiceLayerTest` — unit tests for the services.

Run with:
```bash
docker exec -it php-laravel-web php artisan test
```

---

## 9. What we learned

- **Concurrency on the web**: validating at the end isn't enough, you need to lock during the process.
- **Integrating with external APIs**: always wrap it in try/catch, always use a short timeout, and degrade gracefully when it goes down.
- **Laravel's scheduler**: far more maintainable than scattered shell cronjobs.
- **Docker**: volume permissions between host and container are the #1 source of bugs — solved with an entrypoint that fixes the UID on startup.
- **Idempotent migrations**: detect existing duplicates before adding unique constraints.

---

## 10. Next steps

- A real payment gateway (Stripe).
- Emailing tickets with a QR code.
- A sales analytics panel.
- PWA / push notifications for confirmations.

---

**Repository:** see `README.md` at the project root for installation instructions.
