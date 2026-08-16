# 📊 Business Logic and Processes

> Documentation of the system's core processes: purchase flows, seat locking, validation, and business rules.

---

## Table of Contents

1. [Purchase Flow (3 Steps)](#purchase-flow-3-steps)
2. [Seat Locking Mechanism](#seat-locking-mechanism)
3. [Price Calculation](#price-calculation)
4. [Role-Based Access Control](#role-based-access-control)
5. [Ticket Validation (QR)](#ticket-validation-qr)
6. [TMDB Integration](#tmdb-integration)
7. [Session State Diagram](#session-state-diagram)
8. [Test Data](#test-data)

---

## Purchase Flow (3 Steps)

### 1️⃣ Step 1: Select Ticket Type and Quantity

#### Goal
The user selects **how many tickets** of each type to buy. No seats are selected yet.

#### Ticket Types

| Type | Factor | Description | Base Price (€) | Final Price |
|------|--------|-------------|-----------------|--------------|
| `adult` | 1.00 | Adult ticket | 10.50 | 10.50 |
| `reduit` | 0.80 | Reduced ticket (student/senior) | 10.50 | 8.40 |
| `familia` | 0.82 | Family ticket (group of 4+) | 10.50 | 8.61 |
| `jubilat` | 0.70 | Senior | 10.50 | 7.35 |

**Price Formula**:
```
precio_final = precio_base × factor_tipo
```

#### Validations

```php
// CompraController@step1Store()

1. Session must exist:
   if (!$sesion = Sesion::find($request->sesion_id)) 
       → Error 404

2. Session cannot be in the past:
   if ($sesion->fecha_hora < now())
       → Error: "The session has already started"

3. Minimum 1 ticket:
   if (array_sum($entrades) < 1)
       → Error 422: "Select at least 1 ticket"

4. Maximum 10 tickets per transaction:
   if (array_sum($entrades) > 10)
       → Error 422: "Maximum 10 tickets per transaction"

5. All quantities must be positive:
   foreach ($entrades as $tipo => $cant)
       if ($cant < 0) → Error
```

#### Session Storage

```php
// resources/views/compra/step1.blade.php → POST /comprar/entrades

session(['compra' => [
    'sesion_id'    => 5,
    'entrades'     => [
        'adult'   => 2,
        'reduit'  => 1,
        'familia' => 0,
        'jubilat' => 0
    ],
    'num_entrades' => 3,
    'total'        => 10.50 * 1.00 * 2 + 10.50 * 0.80 * 1 = 29.40
]]);

// At this point, no seats or personal data are stored yet
```

#### Control Flow

```
[User on Movie page]
         ↓
[GET /comprar?sesion_id=5]
         ↓
[Validates session exists and isn't in the past]
         ↓
[Shows step 1: type selector + quantities]
         ↓
[User selects: 2 adults, 1 reduced, total quantity: 3]
         ↓
[POST /comprar/entrades]
         ↓
[Validates quantities (min 1, max 10)]
         ↓
[Calculates total = 29.40€]
         ↓
[session['compra'] = {...}]
         ↓
[302 Redirect → GET /comprar/butaques]
```

---

### 2️⃣ Step 2: Select Seats with Real-Time Locking

#### Goal
The user selects **exactly** `num_entrades` available seats, which get **temporarily locked** (8 minutes) to prevent other users from buying them.

#### Seat States

```
┌─────────────────────────────────────────────┐
│ SEAT MAP - SESSION 5 (180 seats)             │
├─────────────────────────────────────────────┤

[A1] [A2] [A3] [A4]   A = Free (gray)
[A5] [A6] [A7] [A8]   ✓ = Selected (green)
                      ✗ = Sold (red)
[B1] [B2] [B3] [B4]   ⏱ = Locked (orange)
[B5] [B6] [B7] [B8]

DB State:
────────────
A1: FREE          → Doesn't exist in reservas or seat_locks
A6: LOCKED        → SeatLock{butaca='A6', 
                     expires_at=2026-04-18 10:08:00, user_id=7} exists
B2: SOLD          → Reserva{butaca='B2', 
                     status='confirmada'} exists
B5: SELECTED      → SeatLock{butaca='B5', 
                     expires_at=2026-04-18 10:08:00, user_id=NULL} exists
                     (locked by the current user)
```

#### Temporary Locking Mechanism

**Problem it solves**: Preventing double-booking
- User A selects seat B1 and waits 10 min before paying
- Meanwhile, User B sees B1 as available and buys it
- Result: Conflict

**Solution**: SeatLock with expiration

```sql
CREATE TABLE seat_locks (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    sesion_id       BIGINT NOT NULL,
    butaca          VARCHAR(10) NOT NULL,     -- "A1", "B15", etc.
    user_id         BIGINT,                   -- NULL if guest
    created_at      TIMESTAMP DEFAULT NOW(),
    expires_at      TIMESTAMP,                -- NOW() + 15 min
    UNIQUE(sesion_id, butaca),
    FOREIGN KEY (sesion_id) REFERENCES sesions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Example:
INSERT INTO seat_locks (sesion_id, butaca, user_id, expires_at) 
VALUES (5, 'A1', 7, '2026-04-18 10:15:00');
```

#### Locking Flow (in Real Time)

```
[Page loaded: GET /comprar/butaques]
         ↓
[JavaScript loads initial map: GET /api/seats/5]
         ↓
[Response: {taken: ["B2", "C5"], locked: ["A6"], available: 176}]
         ↓
[Renders map: A6 orange, B2/C5 red, rest gray]
         ↓
[User clicks seat A1 (gray)]
         ↓
[jQuery/Fetch: POST /seat/lock {sesion_id: 5, butaca: 'A1'}]
         ↓
┌─────────────────────────────────────────┐
│ ON THE SERVER:                           │
│ 1. Validate CSRF → ✓                     │
│ 2. Check concurrency:                    │
│    - Does SeatLock(A1, sesion=5)         │
│      AND expires_at > NOW() exist?       │
│    - If yes → {ok: false, reason:        │
│      "locked"}                           │
│    - If no → Continue                    │
│ 3. Check sale:                           │
│    - Does Reserva(A1, sesion=5)          │
│      AND status='confirmada' exist?      │
│    - If yes → {ok: false, reason:        │
│      "taken"}                            │
│    - If no → Continue                    │
│ 4. Create SeatLock:                      │
│    INSERT INTO seat_locks                │
│    (sesion_id, butaca, user_id,          │
│     expires_at) VALUES                   │
│    (5, 'A1', NULL, NOW()+8min)           │
│ 5. Return {ok: true, lock_id: 999}       │
└─────────────────────────────────────────┘
         ↓
[SUCCESS: Response received with {ok: true}]
         ↓
[Seat A1 turns GREEN on the map]
         ↓
[Counter: "1 seat selected"]
         ↓
[User selects B1 and C5 as well]
         ↓
[Same process for each seat]
         ↓
[Counter: "Selected: 3 / 3 seats"]
         ↓
["NEXT" button becomes enabled]
         ↓
[User clicks "NEXT"]
         ↓
[POST /comprar/butaques {butaques: "A1,B1,C5"}]
         ↓
┌─────────────────────────────────────────┐
│ ON THE SERVER (step2Store):              │
│ 1. Validate quantity == num_entrades ✓  │
│ 2. RE-VALIDATE availability (final       │
│    check before confirming):             │
│    - Are all 3 seats still free?        │
│    - If not → Error 409 (Conflict)       │
│ 3. Store in session:                     │
│    session['compra']['butaques'] =       │
│    ['A1', 'B1', 'C5']                    │
│ 4. Redirect to Step 3                    │
└─────────────────────────────────────────┘
         ↓
[302 Redirect → GET /comprar/pagament]
```

#### Cleanup of Expired Locks

```php
// SeatLock model
public static function clearExpired()
{
    self::where('expires_at', '<', now())->delete();
}

// Executed on:
// - GET /comprar/butaques (page load)
// - GET /api/seats/{sesion} (refresh)
// - POST /seat/lock (before creating)
```

#### Map Polling (Periodic AJAX)

```javascript
// resources/views/compra/step2.blade.php
const sesionId = {{ $sesion->id }};
const numEntrades = {{ session('compra.num_entrades') }};
let selectedSeats = [];

// Poll every 10 seconds to refresh status
setInterval(async () => {
    const res = await fetch(`/api/seats/${sesionId}`);
    const data = await res.json();
    
    // Regenerate map colors
    data.taken.forEach(butaca => {
        document.querySelector(`[data-seat="${butaca}"]`)
            .classList.remove('available');
        document.querySelector(`[data-seat="${butaca}"]`)
            .classList.add('taken');
    });
    
    data.locked.forEach(butaca => {
        if (!selectedSeats.includes(butaca)) {
            document.querySelector(`[data-seat="${butaca}"]`)
                .classList.add('locked');
        }
    });
}, 10000);
```

---

### 3️⃣ Step 3: Personal Details and Payment

#### Goal
The user fills in their details and **confirms the purchase**, creating the reservation in the DB.

#### Required Fields

```
Name: "Juan" (min 3 characters)
Last name: "García López" (min 3 characters)
Email: "juan@example.com" (valid format)
Payment method: "targeta" (card) or "bizum"
(If card):
  - Number: "4111111111111111" (must pass Luhn)
  - Cardholder: "JUAN GARCIA"
  - Expiry (MM/YYYY): "12/27"
  - CVV: "123" (3-4 digits)
```

#### Confirmation Process

```
[POST /comprar/pagament]
     ↓
┌──────────────────────────────────────────┐
│ DB TRANSACTION (BEGIN)                    │
├──────────────────────────────────────────┤
│ 1. Validate submitted data                │
│    - Luhn check: ✓ Valid card            │
│    - Email: ✓ Format                     │
│ 2. Create Reserva:                       │
│    INSERT INTO reservas (                 │
│        usuario_id, sesion_id,             │
│        butaques, total,                   │
│        status, ticket_token,              │
│        email, nom, cognoms                │
│    ) VALUES (                             │
│        NULL, 5, "A1,B1,C5",               │
│        29.40, 'confirmada',               │
│        'eyJ0eXAiOi...', 'juan@example.com'│
│    );                                     │
│    → reserva_id = 125                     │
│ 3. Create ReservaSeat records:            │
│    For each seat ['A1', 'B1', 'C5']:      │
│    INSERT INTO reserva_seats (            │
│        reserva_id, butaca,                │
│        tipo_entrada, preu                 │
│    ) VALUES (125, 'A1', 'adult', 10.50); │
│ 4. Release locks:                         │
│    DELETE FROM seat_locks                 │
│    WHERE sesion_id=5 AND                  │
│    butaca IN ('A1', 'B1', 'C5')           │
│ 5. Store payment data (last 4 only):      │
│    UPDATE reservas SET                    │
│    tarjeta_ultim_4='1111',                │
│    metode='targeta'                       │
│    WHERE id=125                           │
│ COMMIT                                    │
└──────────────────────────────────────────┘
     ↓
[✓ Reservation created (confirmed)]
     ↓
[Send email with ticket]
     ↓
[Clear session['compra']]
     ↓
[302 Redirect → GET /comprar/confirmacio]
     ↓
[Show: ✅ Purchase completed, Code: #125]
```

#### Final Total Calculation

```php
// In CompraController@step3Store()

$total = 0;
$tipos_precios = [
    'adult'   => 10.50 * 1.00,
    'reduit'  => 10.50 * 0.80,
    'familia' => 10.50 * 0.82,
    'jubilat' => 10.50 * 0.70
];

foreach (session('compra.entrades') as $tipo => $cantidad) {
    $total += $tipos_precios[$tipo] * $cantidad;
}

// Example:
// adult: 2 × 10.50 = 21.00
// reduit: 1 × 8.40 = 8.40
// TOTAL = 29.40
```

---

## Seat Locking Mechanism

### Concept: Race Condition Problem

*Scenario without SeatLock*:

```
Timeline:
08:00:00  User A (GET /api/seats/5) → A1 appears available ✓
08:00:01  User B (GET /api/seats/5) → A1 appears available ✓
08:00:05  User A (POST /comprar/pagament) → Create Reserva(A1) ✓
08:00:06  User B (POST /comprar/pagament) → Create Reserva(A1) ← DUPLICATE KEY ERROR!
          → User B's transaction FAILS

Problem: Bad UX, unpredictable for the user
```

*Scenario with SeatLock*:

```
Timeline:
08:00:00  User A (POST /seat/lock {A1}) → SeatLock(A1, expires=08:08)
          Response: {ok: true, status: "locked"}
08:00:01  User B (POST /seat/lock {A1}) → SeatLock exists (not expired)
          Response: {ok: false, reason: "locked", message: "..."}
          → User B's UI shows: "⏱ Seat locked by another user"
08:00:05  User A (POST /comprar/pagament) → OK, seats still locked
08:00:15  SeatLock({A1, user_id=NULL}) EXPIRES
          → DELETE automatically (if cron runs)

Advantage: User B knows before filling out the form that A1 isn't available
```

### Table: seat_locks

```sql
CREATE TABLE seat_locks (
    id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    sesion_id  BIGINT NOT NULL,
    butaca     VARCHAR(10) NOT NULL,
    user_id    BIGINT,                     -- NULL if guest without auth
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,         -- Created + 8 min
    
    UNIQUE KEY unique_seat_per_session (sesion_id, butaca),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (sesion_id) REFERENCES sesions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### Lifecycle

```
STATE 1: Doesn't exist
  User makes GET /comprar/butaques
  → Query: SELECT * FROM seat_locks WHERE sesion_id=5
  → (none) → State: AVAILABLE
  
STATE 2: Locked (< 8 minutes)
  User A: POST /seat/lock {sesion_id:5, butaca:'A1'}
  → INSERT INTO seat_locks (...) VALUES (5, 'A1', 7, NOW()+8min)
  → State: LOCKED
  → If User B tries: POST /seat/lock {sesion_id:5, butaca:'A1'}
    → Check: WHERE sesion_id=5 AND butaca='A1' 
      AND expires_at > NOW()
    → ✓ Exists → Reject
  
STATE 3: Expired (> 8 minutes)
  Cron job: php artisan schedule:run
  → SeatLock::clearExpired()
  → DELETE FROM seat_locks WHERE expires_at < NOW()
  → State goes back to AVAILABLE
  
STATE 4: Manually released
  After step3Store:
  → DELETE FROM seat_locks 
    WHERE sesion_id=5 AND butaca IN ('A1', 'B1', 'C5')
  → State: AVAILABLE (for others)
```

### Concurrency Validations

```php
// app/Services/SeatAvailabilityService.php

public static function getAvailable($sesion_id) {
    $sesion = Sesion::with('sala')->find($sesion_id);
    
    // Get all seats in the room
    $all = $sesion->sala->getAllSeats(); // ['A1', 'A2', ..., 'J12']
    
    // Get occupied (confirmed) seats
    $taken = Reserva::where('sesion_id', $sesion_id)
                     ->where('status', 'confirmada')
                     ->pluck('butaca') // "A1,B5,..." → explode
                     ->flatten()
                     ->toArray();
    
    // Get locked (not expired) seats
    $locked = SeatLock::where('sesion_id', $sesion_id)
                      ->where('expires_at', '>', now())
                      ->pluck('butaca')
                      ->toArray();
    
    // Combined
    $unavailable = array_merge($taken, $locked);
    
    return [
        'available' => count($all) - count(array_unique($unavailable)),
        'total'     => count($all),
        'taken'     => $taken,
        'locked'    => $locked,
        'sesion_id' => $sesion_id
    ];
}
```

---

## Price Calculation

### Base Formula

```
Unit Price = Base Price × Type Factor × Quantity
Purchase Total = Σ Unit Price for each type
```

### Factor Table

```
┌──────────────────────────────────────────────┐
│ Ticket Type and Discount Factors             │
├──────────────┬──────────┬─────────────────────┤
│ Type         │ Factor   │ Applies to          │
├──────────────┼──────────┼─────────────────────┤
│ adult        │ 1.00     │ Full price          │
│ reduit       │ 0.80     │ -20% students       │
│ familia      │ 0.82     │ -18% groups of 4+   │
│ jubilat      │ 0.70     │ -30% seniors        │
└──────────────┴──────────┴─────────────────────┘
```

### Calculation Example

```
Purchase: 2 adults + 1 reduced + 1 senior
Base price: 10.50€

Breakdown:
┌────────────────────────────────────┐
│ Adults:  10.50 × 1.00 × 2 = 21.00€ │
│ Reduced: 10.50 × 0.80 × 1 = 8.40€  │
│ Senior:  10.50 × 0.70 × 1 = 7.35€  │
├────────────────────────────────────┤
│ TOTAL:                     = 36.75€│
└────────────────────────────────────┘
```

### Validations

```php
// app/Http/Controllers/CompraController.php

CONST TIPOS = [
    'adult'   => ['factor' => 1.00, 'name' => 'Adulto'],
    'reduit'  => ['factor' => 0.80, 'name' => 'Reducida'],
    'familia' => ['factor' => 0.82, 'name' => 'Familia'],
    'jubilat' => ['factor' => 0.70, 'name' => 'Senior']
];

CONST PRECIO_BASE = 10.50;

// Validate that quantity is positive
if ($cantidad < 0) {
    throw new ValidationException("Quantity cannot be negative");
}

// Calculate
$subtotal = 0;
foreach ($entrades as $tipo => $cantidad) {
    if (!isset(self::TIPOS[$tipo])) {
        throw new InvalidArgumentException("Invalid type: $tipo");
    }
    $precio_tipo = self::PRECIO_BASE 
                   × self::TIPOS[$tipo]['factor'] 
                   × $cantidad;
    $subtotal += $precio_tipo;
}
```

---

## Role-Based Access Control

### RBAC Matrix (Role-Based Access Control)

```
┌─────────────────────────────────────────────────────────┐
│              PERMISSIONS BY ROLE                         │
├──────────┬───────┬─────────┬──────────┬────────────────┤
│ Action   │ Guest │ Client  │ Box office│ Admin          │
├──────────┼───────┼─────────┼──────────┼────────────────┤
│ View Home│  ✓    │   ✓     │    ✓     │      ✓         │
│ Buy      │  ✓*   │   ✓     │    ✓     │      ✓         │
│ My profile│  ✗   │   ✓     │    ✓     │      ✓         │
│ My tickets│  ✗   │   ✓     │    ✓     │      ✓         │
│ Admin    │  ✗    │   ✗     │    **    │      ✓         │
│ CRUD movies│ ✗   │   ✗     │    **    │      ✓         │
│ CRUD sessions│ ✗ │   ✗     │    ✓     │      ✓         │
│ Reports  │  ✗    │   ✗     │    ✓     │      ✓         │
└──────────┴───────┴─────────┴──────────┴────────────────┘

* Guest: Can buy BUT without a verified email
** Box office: Can view/edit their cinema's sessions, reports
✗ = Not allowed
✓ = Allowed
** = Partial (only their cinema/data)
```

### Definition in Code

```php
// app/Models/User.php

enum Role: string {
    case ADMIN = 'admin';
    case TAQUILLA = 'taquilla';
    case CLIENTE = 'cliente';
    case GUEST = 'guest';
}

public function isAdmin(): bool {
    return $this->role === Role::ADMIN->value;
}

public function isTaquilla(): bool {
    return $this->role === Role::TAQUILLA->value;
}

public function isCliente(): bool {
    return $this->role === Role::CLIENTE->value;
}

public function canManageCine(): bool {
    return $this->isAdmin() || $this->isTaquilla();
}

// app/Http/Middleware/RoleMiddleware.php

public function handle($request, Closure $next, ...$roles) {
    $user = auth()->user();
    
    if (!$user || !in_array($user->role, $roles)) {
        abort(403, 'Unauthorized');
    }
    
    return $next($request);
}

// routes/web.php
Route::get('/admin/sesiones', [SesionController::class, 'index'])
    ->middleware('auth', 'role:admin,taquilla');
```

### Authentication Flow

```
1. User accesses GET /comprar
   ├─ If authenticated → auth()->user() gets User
   ├─ If not authenticated → auth()->guest() = true
   └─ Either way → Can continue (guest checkout)

2. During checkout, data is collected:
   - If guest → create temporary User OR store as Reserva with no user
   - If client → link Reserva to User.id

3. On confirmation:
   INSERT INTO reservas (usuario_id, ...) VALUES (null, ...) -- Guest
   OR
   INSERT INTO reservas (usuario_id, ...) VALUES (7, ...)    -- Client
```

---

## Ticket Validation (QR)

### Generation Flow

```
[User completes purchase successfully]
     ↓
[CompraController@step3Store creates Reserva]
     ↓
┌──────────────────────────────────────┐
│ Generate JWT Token                    │
├──────────────────────────────────────┤
│ Header: {alg: "HS256", typ: "JWT"}   │
│ Payload: {                            │
│   reserva_id: 125,                    │
│   sesion_id: 5,                       │
│   butacas: ["A1", "B1", "C5"],        │
│   email: "juan@example.com",          │
│   exp: now()+3hours (event time)      │
│ }                                     │
│ Signature: HMAC-SHA256(secret)        │
└──────────────────────────────────────┘
     ↓
[Store in Reserva.ticket_token]
     ↓
[Generate QR for: /entrada/qr/{token}]
     ↓
[Send in email to the user]
```

### Validation at the Door

```
[User presents QR at box office]
     ↓
[Box office scans QR → gets token]
     ↓
[GET /entrada/qr/{token}]
     ↓
┌─────────────────────────────────────────────┐
│ TicketController@qr                         │
├─────────────────────────────────────────────┤
│ 1. Decode JWT:                               │
│    - Validate signature (token untampered?) │
│    - Validate exp (still valid?)             │
│    - Get payload                             │
│ 2. Look up Reserva in DB:                    │
│    SELECT * FROM reservas                    │
│    WHERE id=payload.reserva_id               │
│    AND status='confirmada'                   │
│ 3. Check status:                             │
│    - Already used? (check used_at column)   │
│    - Is it today? (check sesion.fecha_hora) │
│    - Not cancelled?                          │
│ 4. If everything OK → mark as used:          │
│    UPDATE reservas                           │
│    SET used_at = NOW()                       │
│    WHERE id = payload.reserva_id             │
│    Status: ✅ VALID                          │
│ 5. If it fails → Status: ❌ INVALID          │
│    Reason: "ALREADY USED" or "EXPIRED" etc  │
└─────────────────────────────────────────────┘
     ↓
[Shows QR: GREEN ✅ (valid) or RED ❌ (used)]
```

### QR Endpoints

```
GET /entrada/qr/{token}
  → HTML with QR + status
  
GET /entrada/qr/{token}?format=pdf
  → Downloadable PDF (pocket ticket)
  
GET /api/entrada/validate/{token}
  → JSON {valid: true/false, status: "used"/"expired"/...}
```

---

## TMDB Integration

### Movie Fetching Flow

```
[Admin: GET /admin/peliculas/buscar-tmdb?q=terminator]
     ↓
┌──────────────────────────────────────────────┐
│ DevsApiHubMovieService::search($query)       │
├──────────────────────────────────────────────┤
│ GET /discover/movie                          │
│ ?query=terminator                            │
│ &primary_release_year=2024-2026              │
│ &api_key={TMDB_API_KEY}                      │
│ → Response: 20 movies                        │
└──────────────────────────────────────────────┘
     ↓
[Maps titles + poster_path + synopsis to UI]
     ↓
[User selects "Terminator: Dark Fate"]
     ↓
[Saved as local Pelicula + poster_path]
```

### Movie Cache (Fallback)

```php
// Implemented directly inside DevsApiHubMovieService.php
// Every successful call caches its result as a fallback

public function getNowPlaying(): array
{
    try {
        $normalized = $this->normalizeMovies($all);

        // Cache the valid response for 1 hour as fallback
        if (! empty($normalized)) {
            Cache::put('tmdb_now_playing', $normalized, now()->addHour());
        }

        return $normalized;
    } catch (\Throwable $e) {
        // If TMDB goes down, serve the last valid data
        return Cache::get('tmdb_now_playing', []);
    }
}

// searchByQuery() uses a 30-minute cache per query (md5 of the search term)
// getAll() uses a 1-hour cache under the key 'tmdb_all'
```

### Data Structures

```json
{
  "id": 140607,
  "title": "Terminator: Dark Fate",
  "original_title": "Terminator: Dark Fate",
  "overview": "More than two decades...",
  "poster_path": "/9M82HW1vFJrSGpNNQqwLVOr0FKm.jpg",
  "release_date": "2019-11-01",
  "popularity": 54.4,
  "vote_average": 6.5,
  "vote_count": 5432
}
```

### Image Handling

```php
// app/Models/Pelicula.php

protected $appends = ['poster_url'];

public function getPosterUrlAttribute() {
    if (!$this->poster_path) {
        // Default image
        return asset('images/no-poster.png');
    }
    
    // If poster_path is a relative path:
    // "/9M82HW1vFJrSGpNNQqwLVOr0FKm.jpg"
    if (strpos($this->poster_path, '/') === 0) {
        // TMDB prefix
        return config('services.movies_api.image_base_url') 
               . 'w500'
               . $this->poster_path;
    }
    
    // If it's a remote URL (uncommon):
    return $this->poster_path;
}

// In the view:
<img src="{{ $pelicula->poster_url }}" alt="...">
```

---

## Session State Diagram

```
SHOPPING CART - Session Array
═════════════════════════════════════════

session['compra'] = [
    'sesion_id'     => 5,              // Selected movie session
    'entrades'      => [               // Quantity per type
        'adult'   => 2,
        'reduit'  => 1,
        'familia' => 0,
        'jubilat' => 0
    ],
    'num_entrades'  => 3,              // Total (for validation)
    'total'         => 29.40,          // Sum computed on the server
    'butaques'      => [               // Filled in step 2
        'A1',
        'B1',
        'C5'
    ],
    'email'         => 'juan@...',    // Filled in step 3
    'nom'           => 'Juan',         // Same
    'cognoms'       => 'García',       // Same
    'metode_pago'   => 'targeta',      // Same
]

Transitions:
─────────────

START → Step 1 (select types)
  Add: sesion_id, entrades, num_entrades, total
  State: PARTIAL

  ↓ 
Step 2 (select seats)
  Add: butaques = ['A1', 'B1', 'C5']
  State: VALID (ready for payment)

  ↓
Step 3 (payment)
  Add: email, nom, cognoms, metode_pago
  State: COMPLETE

  ↓
[POST /comprar/pagament]
  → Create Reserva
  → Clear: session['compra'] = null
  State: FINISHED (empty session)
```

---

## Test Data

### Users Created via "php artisan db:seed"

```
┌──────────────────────────────────────────────────┐
│         TEST USERS                                │
├──────────┬──────────────────┬────────────────────┤
│ Role     │ Email             │ Password           │
├──────────┼──────────────────┼────────────────────┤
│ Admin    │ admin@cineflow    │ password           │
│ Box office│ taquilla@cineflow│ password           │
│ Client   │ cliente@cineflow  │ password           │
└──────────┴──────────────────┴────────────────────┘
```

### Test Cinemas

```
1. Cinemes Barcelona (Centro)
   - Screens: 4
   - Seats per screen: 180 (12 rows × 15 columns)
   - Address: Paseo de Gracia, Barcelona

2. Cinemes L'Illa (Diagonal)
   - Screens: 2
   - Seats per screen: 150
```

### Test Sessions

- Today at 18:00, 20:30, 23:00
- Tomorrow at 16:00, 18:30

### Test Cards (Stripe format, not a real integration)

```
4111 1111 1111 1111      Success
4000 0000 0000 0002      Declined (simulated)
5555 5555 5555 4444      Diner's Club (simulated)
```

---

## Business Rules Summary

| Rule | Validation | Location |
|-------|----------|-------|
| Min 1, max 10 tickets | `count($entrades) >= 1 AND <= 10` | step1Store |
| Session not in the past | `$sesion->fecha_hora > now()` | step1 |
| Seat count == ticket count | `count($butaques) == num_entrades` | step2Store |
| Seats available | Not in confirmed reservations + not in active locks | step2/ step3 |
| Lock expiration | `expires_at = created_at + 15 minutes` | SeatLock |
| Unit price | `preu_base × factor_tipo × cantidad` | step1Store |
| Valid QR | Valid JWT + confirmed Reserva + not used | qr endpoint |
| Admin access | `role == 'admin'` | RoleMiddleware |
</content>
