# 🏗 Technical Architecture · CineFlow

> Complete documentation of the architecture, patterns, components, and technology decisions.

---

## Table of Contents

1. [Overview](#overview)
2. [Layered Architecture](#layered-architecture)
3. [Design Patterns](#design-patterns)
4. [Main Components](#main-components)
5. [Data Flows](#data-flows)
6. [Technology Decisions](#technology-decisions)
7. [Dependencies and Coupling](#dependencies-and-coupling)
8. [Scalability and Performance](#scalability-and-performance)

---

## Overview

**CineFlow** follows a **layered architecture** with **separation of concerns**:

```
┌─────────────────────────────────────────────────────┐
│  📱 Presentation                                    │
│  (Blade templates + Tailwind CSS + Alpine.js)       │
├─────────────────────────────────────────────────────┤
│  🕹 Controllers (HTTP Request Handlers)             │
│  (Routing, input validation, delegation)            │
├─────────────────────────────────────────────────────┤
│  ⚙️ Services (Business Logic)                       │
│  (Orchestration, external APIs, transactions)       │
├─────────────────────────────────────────────────────┤
│  💾 Models (Eloquent ORM + Accessors)               │
│  (DB relationships, casting, data transformation)   │
├─────────────────────────────────────────────────────┤
│  🔄 Database (MySQL 8.0)                            │
│  (Tables, indexes, ACID transactions)               │
└─────────────────────────────────────────────────────┘
        ↓
   🌐 TMDB API (The Movie Database)
```

---

## Layered Architecture

### 1️⃣ Presentation Layer

**Responsibility**: Render the user interface

**Technologies**:
- **Blade**: Laravel's templating engine
- **Tailwind CSS**: Utility styles (compiled via Vite)
- **Alpine.js**: Lightweight DOM interactivity
- **Fetch API**: AJAX communication

**Main files**:

```
resources/views/
├── layouts/
│   ├── app.blade.php            # Main layout
│   ├── auth.blade.php           # Login/register
│   └── dashboard.blade.php      # Admin
├── compra/
│   ├── step1.blade.php          # Select ticket types
│   ├── step2.blade.php          # Seat map
│   ├── step3.blade.php          # Payment
│   ├── _sidebar.blade.php       # Purchase summary (reusable)
│   └── confirmacio.blade.php    # Success
├── peliculas/
│   ├── index.blade.php          # Catalog
│   ├── show.blade.php           # Movie detail
│   └── _grid.blade.php          # Card component
├── admin/
│   ├── peliculas/
│   ├── salas/
│   ├── sesiones/
│   └── usuarios/
└── emails/
    ├── reserva-confirmada.blade.php
    └── entrada-qr.blade.php
```

**Example: rendering a movie with its poster (accessor)**

```blade
<!-- resources/views/peliculas/_card.blade.php -->
<div class="card">
    <img src="{{ $pelicula->poster_url }}" alt="...">
    {{-- Accessor: Pelicula::getPosterUrlAttribute() --}}
    {{-- Result: https://image.tmdb.org/t/p/w500/9M82HW1vFJrSGpNNQqwLVOr0FKm.jpg --}}
    
    <h3>{{ $pelicula->titulo }}</h3>
    <p>{{ Str::limit($pelicula->sinopsis, 100) }}</p>
    <a href="/peliculas/{{ $pelicula->id }}">View screenings</a>
</div>
```

---

### 2️⃣ Controller Layer

**Responsibility**: Orchestrate HTTP request → response

**Pattern**: Resource-oriented (RESTful where it applies)

**Structure**:

```php
// app/Http/Controllers/CompraController.php

class CompraController extends Controller {
    
    // GET /comprar?sesion_id=5
    public function step1() { /* ... */ }
    
    // POST /comprar/entrades
    public function step1Store() { /* ... */ }
    
    // GET /comprar/butaques
    public function step2() { /* ... */ }
    
    // POST /comprar/butaques
    public function step2Store() { /* ... */ }
    
    // GET /comprar/pagament
    public function step3() { /* ... */ }
    
    // POST /comprar/pagament
    public function step3Store() { 
        // NOT: $reserva = Reserva::create(...);
        // INSTEAD: $reserva = $this->purchaseService->complete($data);
    }
}
```

**Typical methods**:
- `index()` / `show()` → Reads (views)
- `create()` / `edit()` → Forms
- `store()` / `update()` → Processing (delegate to Services)

**Validation**:
- Custom Form Requests (`app/Http/Requests/`)
- Immediate rules (existence, format)
- Complex logic → Services

**Things a Controller should NOT do**:
- ❌ Complex queries (↓ Models)
- ❌ Business calculations (↓ Services)
- ❌ API integrations (↓ Services)
- ❌ DB transactions (↓ Services/Models)

---

### 3️⃣ Service Layer

**Responsibility**: Pure business logic

**Principle**: Orchestrate Models, external APIs, transactions

**Service stack** (7 real services in the project):

```php
// app/Services/

1. PurchaseService.php
   - confirmPurchase(): ACID transaction with lockForUpdate()
   - Creates Reserva + ReservaSeat + releases SeatLocks
   - Automatic retries (attempts: 3) in case of deadlock

2. SeatAvailabilityService.php
   - getReservedSeats(sesion_id): Confirmed seats
   - getAvailableCount(sesion_id): Count of free seats
   - Direct DB queries (no TTL cache, to guarantee integrity)

3. DevsApiHubMovieService.php
   - getAll(), getNowPlaying(): TMDB catalog with fallback cache (1h)
   - searchByQuery(query): Free-text search with cache (30 min)
   - getById(id): Detail with runtime, genres, and age rating

4. CachedMovieService.php
   - Wrapper around DevsApiHubMovieService
   - Caches results to reduce API calls

5. GuestCheckoutService.php
   - getOrCreateGuestUser(): Creates a temporary user for checkout without registration
   - Generates a random password and marks the email as verified

6. TicketService.php
   - generateTicketToken(): HMAC-SHA256 signed with APP_KEY
   - verifyTicketToken(): Ticket validation at the box office
   - Generates the payload for the QR code

7. TmdbMovieService.php
   - Alternative implementation of the TMDB service
   - Used as a fallback or for specific configurations
```

**ACID Transactions**:

```php
// app/Services/PurchaseService.php

public function completeStep3($data): Reserva {
    return DB::transaction(function () use ($data) {
        // 1. Create Reserva
        $reserva = Reserva::create([
            'usuario_id'   => auth()->id() ?? null,
            'sesion_id'    => session('compra.sesion_id'),
            'total'        => session('compra.total'),
            'status'       => 'confirmada',
            'ticket_token' => $this->qrService->generateToken(...)
        ]);
        
        // 2. Create ReservaSeat (normalization)
        foreach (session('compra.butaques') as $butaca) {
            ReservaSeat::create([
                'reserva_id'    => $reserva->id,
                'butaca'        => $butaca,
                'tipo_entrada'  => 'adult', // Simplified
            ]);
        }
        
        // 3. Release SeatLocks
        SeatLock::where('sesion_id', $reserva->sesion_id)
                 ->whereIn('butaca', session('compra.butaques'))
                 ->delete();
        
        // 4. Send email
        Mail::to($data['email'])->send(
            new ReservaConfirmada($reserva)
        );
        
        return $reserva;
        // If any step inside the TRANSACTION fails → automatic ROLLBACK
    });
}
```

---

### 4️⃣ Model Layer (Eloquent ORM)

**Responsibility**: Data representation + relationships + accessors

**Relationships (12 FKs)**:

```php
// app/Models/

User ┬─→ hasMany Reserva
     └─→ hasMany SeatLock

Pelicula ┬─→ belongsToMany Categoria (via pelicula_categoria)
         └─→ hasMany Sesion

Categoria ┬─→ belongsToMany Pelicula
          └─→ hasMany SesionSession

Cine ────→ hasMany Sala

Sala ┬─→ belongsTo Cine
     └─→ hasMany Sesion

Sesion ┬─→ belongsTo Pelicula
       ├─→ belongsTo Sala
       ├─→ hasMany Reserva
       └─→ hasMany SeatLock

Reserva ┬─→ belongsTo User (nullable)
        ├─→ belongsTo Sesion
        └─→ hasMany ReservaSeat

ReservaSeat ┬─→ belongsTo Reserva

SeatLock ┬─→ belongsTo Sesion
         └─→ belongsTo User
```

**Accessors (data transformation)**:

```php
// app/Models/Pelicula.php

class Pelicula extends Model {
    protected $appends = ['poster_url'];
    
    // Access: $pelicula->poster_url
    public function getPosterUrlAttribute(): string {
        if (!$this->poster_path) {
            return asset('images/no-poster.png');
        }
        
        // TMDB returns "/9M82HW..." → prepend the domain
        if (str_starts_with($this->poster_path, '/')) {
            return config('services.movies_api.image_base_url') 
                   . 'w500' 
                   . $this->poster_path;
        }
        
        return $this->poster_path;
    }
}
```

**Scopes (reusable queries)**:

```php
// app/Models/Sesion.php

class Sesion extends Model {
    // public function scopeUpcoming($query) { ... }
    
    public function scopeAtCine($query, $cine_id) {
        return $query->whereHas('sala', function ($q) use ($cine_id) {
            $q->where('cine_id', $cine_id);
        });
    }
    
    public function scopeCurrentOrFuture($query) {
        return $query->where('fecha_hora', '>=', now());
    }
    
    // Usage: Sesion::atCine(1)->currentOrFuture()->get()
}
```

---

### 5️⃣ Database Layer (MySQL 8.0)

**Responsibility**: Persistence, referential integrity, indexes

**9 main tables**:

```sql
CREATE TABLE users ( ... );
CREATE TABLE cines ( ... );
CREATE TABLE salas ( ... );
CREATE TABLE peliculas ( ... );
CREATE TABLE categorias ( ... );
CREATE TABLE pelicula_categoria ( ... );  -- M:M pivot
CREATE TABLE sesions ( ... );
CREATE TABLE reservas ( ... );
CREATE TABLE reserva_seats ( ... );     -- Normalization
CREATE TABLE seat_locks ( ... );        -- Temporary
```

**Critical indexes**:

```sql
-- Look up screenings by movie and cinema
CREATE INDEX idx_sesion_pelicula ON sesions(pelicula_id, sala_id);

-- Fast lookup of sold seats
CREATE INDEX idx_reserva_sesion ON reservas(sesion_id);
CREATE INDEX idx_reserva_seat_reserva ON reserva_seats(reserva_id);

-- Cleanup of expired locks
CREATE INDEX idx_seat_lock_expires ON seat_locks(expires_at);

-- Lookup by user
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_reserva_usuario ON reservas(usuario_id);
```

---

## Design Patterns

### 1. Model-View-Controller (MVC)

```
Request → Router → Controller → Model → View → HTML Response
                     ↓
                  Service → DB Query
```

**Advantages**:
- ✅ Clear separation of concerns
- ✅ Testable (easy to mock Services)
- ✅ Scalable (add Controllers without affecting others)

### 2. Service Layer

**Pattern**: Controllers delegate complex logic to Services

```php
// ❌ BAD: Logic in the Controller
public function step3Store() {
    $total = 0;
    foreach (session('compra.entrades') as $tipo => $cant) {
        $precio = 10.50 * PriceFactors::FACTORS[$tipo] * $cant;
        $total += $precio;
    }
    
    $reserva = Reserva::create(['total' => $total, ...]);
    
    foreach (session('compra.butaques') as $butaca) {
        ReservaSeat::create([...]);
    }
    
    SeatLock::where(...)->delete();
    Mail::send(...);
}

// ✅ GOOD: Logic in a Service
public function step3Store() {
    $reserva = $this->purchaseService->completeStep3(
        $request->validated()
    );
    return redirect('/comprar/confirmacio');
}
```

### 3. Dependency Injection

**Pattern**: Inject dependencies via the constructor

```php
// app/Http/Controllers/CompraController.php

class CompraController extends Controller {
    private PurchaseService $purchaseService;
    private SeatAvailabilityService $seatService;
    
    // Laravel's DI container resolves these automatically
    public function __construct(
        PurchaseService $purchaseService,
        SeatAvailabilityService $seatService
    ) {
        $this->purchaseService = $purchaseService;
        $this->seatService = $seatService;
    }
    
    public function step2() {
        // Use $this->seatService without instantiating it
        $available = $this->seatService->getAvailable(5);
    }
}
```

### 4. Repository Pattern (Implicit in Eloquent)

**Idea**: Models act as data repositories

```php
// COMPLEX QUERIES → Scopes on the Model

// Get upcoming available screenings,
// grouped by movie, with images
$upcoming = Sesion::query()
    ->with('pelicula', 'sala.cine')  // Eager loading
    ->whereDate('fecha_hora', '>=', today())
    ->whereDate('fecha_hora', '<=', today()->addDays(7))
    ->orderBy('fecha_hora')
    ->paginate(20);

// In the Controller:
public function index() {
    return view('sesiones.index', [
        'sesiones' => Sesion::upcomingInWeek()->paginate(20)
    ]);
}
```

### 5. Factory Pattern (Seeders)

**Pattern**: Generate test data consistently

```php
// database/factories/PeliculaFactory.php

class PeliculaFactory extends Factory {
    public function definition(): array {
        return [
            'titulo'      => fake()->title(),
            'sinopsis'    => fake()->paragraph(),
            'duracion'    => fake()->numberBetween(80, 180),
            'poster_path' => '/placeholder.jpg',
            'clasificacion_edad' => fake()->randomElement(['G', 'PG', 'PG-13', 'R']),
        ];
    }
}

// database/seeders/DatabaseSeeder.php
Pelicula::factory(10)->create();  // 10 random movies
```

### 6. Template Method Pattern (Blade)

**Pattern**: Share structure, vary content

```blade
<!-- resources/views/layouts/app.blade.php -->
<div class="layout">
    <header>...</header>
    
    <!-- Variable point: @yield -->
    @yield('content')
    
    <footer>...</footer>
</div>

<!-- resources/views/peliculas/index.blade.php -->
@extends('layouts.app')

@section('content')
    <h1>Now Showing</h1>
    @foreach ($peliculas as $pelicula)
        @include('peliculas._card', ['pelicula' => $pelicula])
    @endforeach
@endsection
```

---

## Main Components

### Controllers (14+)

```
app/Http/Controllers/
├── HomeController.php
├── PeliculaController.php
├── SesionController.php
├── CompraController.php              ← Core 3-step flow
├── ReservaController.php
├── TicketController.php              ← QR validation
├── Admin/
│   ├── PeliculaController.php
│   ├── SalaController.php
│   ├── SesionController.php
│   └── UsuarioController.php
├── Api/
│   ├── SeatController.php            ← /seat/lock, /seat/unlock
│   └── SeatsController.php           ← GET /api/seats/{sesion}
└── Auth/
    └── AuthController.php
```

### Services (7)

| Service | Responsibility |
|----------|-----------------|
| `PurchaseService` | ACID purchase transaction with pessimistic locking |
| `SeatAvailabilityService` | Compute free seats in real time |
| `DevsApiHubMovieService` | Consume the TMDB API with fallback caching |
| `CachedMovieService` | Cache wrapper for DevsApiHub |
| `GuestCheckoutService` | Checkout without registration (temporary user) |
| `TicketService` | Generate + validate HMAC tokens for QR codes |
| `TmdbMovieService` | Alternative TMDB implementation |

### Models (9)

| Model | Expected Rows |
|--------|-----------------|
| `User` | 4 (admin, box office, customer, guest) |
| `Cine` | 2 |
| `Sala` | 4 (2 cinemas × 2 screens) |
| `Pelicula` | 15-20 (initial seed) |
| `Categoria` | 10 (genres) |
| `Sesion` | 200+ (per screen × day) |
| `Reserva` | Dynamic (created by purchases) |
| `ReservaSeat` | Dynamic (3-10 per booking) |
| `SeatLock` | Dynamic (temporary, < 15 min each) |

---

## Data Flows

### Flow 1: Browsing the Catalog

```
┌─────────────────────────────────────┐
│ User visits GET /peliculas          │
└─────────────────────────────────────┘
         ↓
┌───────────────────────────────────────────┐
│ PeliculaController::index()               │
├───────────────────────────────────────────┤
│ $peliculas = Pelicula::with('categorias') │
│    ->where('estado', 'activa')            │
│    ->paginate(12);                        │
│                                           │
│ return view('peliculas.index', [          │
│     'peliculas' => $peliculas             │
│ ]);                                       │
└───────────────────────────────────────────┘
         ↓
┌──────────────────────────────────────┐
│ resources/views/peliculas/index      │
├──────────────────────────────────────┤
│ @foreach ($peliculas as $pelicula)   │
│     <img src="{{ $pelicula->poster   │
│              _url }}" />              │  ← Accessor!
│     {{ $pelicula->titulo }}           │
│ @endforeach                           │
└──────────────────────────────────────┘
         ↓
    ✅ HTML with the movie listing
```

### Flow 2: Purchase with Concurrent Locking

```
┌──────────────────────────────────────┐
│ User POST /comprar/butaques          │ {butacas: ['A1', 'B2']}
│ (AJAX from step2.blade.php)          │
└──────────────────────────────────────┘
         ↓
┌───────────────────────────────────────────────────────────┐
│ SeatController::lock() [Api]                              │
├───────────────────────────────────────────────────────────┤
│ request: {sesion_id: 5, butaca: 'A1'}                     │
│                                                           │
│ 1. $service->isAvailable(5, 'A1') ?                       │
│    SELECT * FROM seat_locks                              │
│    WHERE sesion_id=5 AND butaca='A1'                      │
│    AND expires_at > NOW()                                 │
│    IF exists → return {ok: false, reason: 'locked'}       │
│                                                           │
│ 2. $service->block(5, 'A1')                               │
│    INSERT INTO seat_locks (                               │
│        sesion_id, butaca, expires_at, user_id             │
│    ) VALUES (                                             │
│        5, 'A1', NOW()+15min, auth()->user()->id           │
│    );                                                     │
│                                                           │
│ return json_response(['ok' => true]);                     │
└───────────────────────────────────────────────────────────┘
         ↓
┌──────────────────────────────────────┐
│ JavaScript updates the DOM            │
│ document.querySelector('[data-seat=   │
│  "A1"]').classList.add('selected');   │
└──────────────────────────────────────┘
         ↓
┌──────────────────────────────────────┐
│ User submits the form POST            │
│ /comprar/pagament                     │
└──────────────────────────────────────┘
         ↓
┌──────────────────────────────────────────────────┐
│ CompraController::step3Store()                    │
├──────────────────────────────────────────────────┤
│ $reserva = $this->purchaseService->               │
│     completeStep3($data);                         │
│                                                  │
│ [Inside PurchaseService::completeStep3]:         │
│ 1. Create Reserva (INSERT)                       │
│ 2. Create ReservaSeat x3 (INSERT ×3)             │
│ 3. DELETE seat_locks WHERE butaca IN (...)       │
│ 4. Mail::send(ReservaConfirmada::class)          │
│ [ALL WITHIN DB::transaction() → ROLLBACK on error] │
└──────────────────────────────────────────────────┘
         ↓
    ✅ Booking created, locks released
```

### Flow 3: Ticket Validation (QR)

```
┌─────────────────────────────────────────┐
│ Box office staff scans the ticket's QR   │
│ → /entrada/qr/{JWT_TOKEN}                │
└─────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────────────────┐
│ TicketController::qr($token)                       │
├────────────────────────────────────────────────────┤
│ 1. $data = JWT::verify($token, secret) ?           │
│    IF false → return view('entry.invalid')         │
│                                                   │
│ 2. $reserva = Reserva::find($data['reserva_id']) │
│    IF !$reserva → return error                     │
│                                                   │
│ 3. $reserva->used_at IS NOT NULL ?                │
│    IF true → return view('entry.already_used')    │
│                                                   │
│ 4. $reserva->update(['used_at' => now()]);        │
│                                                   │
│ return view('entry.valid', [                      │
│     'reserva' => $reserva,                        │
│     'qr_code' => ...                              │
│ ]);                                               │
└────────────────────────────────────────────────────┘
         ↓
    ✅ Valid ticket or ❌ Invalid/Already used
```

---

## Technology Decisions

### Why Laravel 12?

| Aspect | Advantage |
|--------|---------|
| **Built-in MVC** | Controllers, Views, Models natively |
| **Eloquent ORM** | Type-safe queries without raw SQL |
| **Security** | CSRF, hashing, auth built in |
| **Migrations** | Automatic DB versioning |
| **Testing** | Native TestCase + Factories |
| **Community** | 50K+ packages via Composer |

### Why MySQL 8.0?

| Aspect | Advantage |
|--------|---------|
| **ACID Transactions** | Guarantees integrity (crucial for purchases) |
| **Foreign Keys** | Automatic referential integrity |
| **Indexes** | Very fast complex queries |
| **Industry Standard** | Well known, well documented, stable |
| **JSON** | Native support (if needed) |

### Why Docker?

| Aspect | Advantage |
|--------|---------|
| **Reproducibility** | Same local env as production |
| **Isolation** | PHP + MySQL without version conflicts |
| **Fast Setup** | `docker-compose up` = environment ready |
| **Scaling** | Easy to replicate containers in production |

### Why TMDB instead of building a local catalog?

| Aspect | Benefit |
|--------|----------|
| **11,000+ movies** | No need to maintain a huge DB |
| **Always up to date** | Posters, synopses always fresh |
| **Free** | Free tier is sufficient for a demo |
| **Learning** | Integrating an external API = a real-world skill |

**Trade-off**: External dependency (TMDB), mitigated with caching (12h) to reduce latency impact.

---

## Dependencies and Coupling

### External Dependencies

```
Application → TMDB API
├─ GET /discover/movie
├─ GET /movie/{id}
└─ Cache: Redis/Memcached (12h)
  [If TMDB doesn't respond → graceful degradation: placeholder]

Application → Mailer (for confirmation emails)
├─ SMTP credentials in .env
└─ Fallback: log driver (dev)

Application → Docker (infrastructure)
├─ PHP 8.4 + Apache
├─ MySQL 8.0
└─ phpMyAdmin (dev only)
```

### Coupling (Low Coupling = Good)

```
✅ LOW COUPLING:

controller.php → Service
Service → Model
Model → Database

Change: "TMDB now uses Protocol Buffers instead of JSON"
Impact: Only DevsApiHubMovieService changes ✓

✅ LOW COUPLING:

Change: "Add Stripe for real payments"
Impact: Add PaymentService::stripe(), slightly tweak step3Store() ✓

❌ HIGH COUPLING (mitigated):

View ← Controller ← Business Logic
Change: Logic in Controller → impossible to reuse from the CLI
Solution: Services + thin Controllers ✓
```

---

## Scalability and Performance

### Current Optimizations

```
1. Eager Loading (Prevents N+1)
   Sesion::with('pelicula', 'sala.cine')->get()
   NOT: foreach ($sesiones as $s) { $s->pelicula }

2. Indexes on PK + FK
   CREATE INDEX idx_sesion_pelicula ON sesions(pelicula_id)
   
3. Pagination (only X results per page)
   Sesion::paginate(20)  // NOT: ->get() the entire table
   
4. Caching
   Cache::remember('upcoming_movies', 12h, fn() => ...)
   
5. Selective columns
   Pelicula::select('id', 'titulo', 'poster_path')->get()
   NOT: Pelicula::get() [fetches all large fields too]
```

### Potential Bottlenecks

| Bottleneck | Cause | Mitigation |
|----------|-------|-----------|
| TMDB API slowness | External network | 12h cache + fallback |
| Seat lock queries | Many screenings | Index on expires_at |
| Sales report | Full table scan | Index on usuario_id + fecha |
| Session payload size | Large session data | Clear it after purchase completes |

### Scaling to Production Level

```
NOW (Monolith):
┌─────────────┐
│ PHP app     │
│ Controllers │
│ Services    │
│ Models      │
└─────────────┘
      ↓
 ┌─────────┐
 │ MySQL   │
 │ single  │
 └─────────┘

FUTURE (Microservices, optional):
┌─────────────────────┐
│ Load Balancer       │
└────────┬────────┬───┘
    ┌────┴──┐  ┌──┴─────┐
    │ App 1 │  │ App 2  │  ← Multiple instances
    └───┬───┘  └──┬─────┘
        └────┬────┘
         ┌───┴──────┐
         │ MySQL    │  ← Replica
         │ Primary  │
         ├──────────┤
         │ MySQL    │
         │ Replica  │
         └──────────┘
         
         ┌─────────────┐
         │ Redis Cache │  ← Distributed
         └─────────────┘
```

---

## Architecture Summary

| Layer | Technology | Responsibility | Examples |
|------|-----------|-----------------|----------|
| **Presentation** | Blade + Tailwind | HTML + CSS | step1.blade.php |
| **HTTP** | Controllers | Request → Response | CompraController |
| **Business** | Services | Pure logic | PurchaseService |
| **Data** | Eloquent Models | ORM + relationships | Pelicula, Reserva |
| **DB** | MySQL 8 | ACID persistence | 9 tables |
| **External** | TMDB API | Movie catalog | DevsApiHubMovieService |

**Philosophy**: "Thin Controllers, Rich Models, Smart Services"
