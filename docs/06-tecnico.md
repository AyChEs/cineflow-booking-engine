# 📖 Technical Documentation — CineFlow

> Everything you need to understand the actual code of the project: why each file does what it does, how the pieces connect, and what decisions were made.

---

## Table of Contents

1. [Key folder structure](#1-key-folder-structure)
2. [The database: our 8 tables](#2-the-database-our-8-tables)
3. [The Models and their relationships](#3-the-models-and-their-relationships)
4. [The Routes: `routes/web.php`](#4-the-routes-routeswebphp)
5. [The access-control Middlewares](#5-the-access-control-middlewares)
6. [The Controllers](#6-the-controllers)
7. [The Role system](#7-the-role-system)
8. [The Blade Views](#8-the-blade-views)
9. [Validation for each form](#9-validation-for-each-form)
10. [Migrations: order matters](#10-migrations-order-matters)
11. [The 3-step purchase flow](#11-the-3-step-purchase-flow)
12. [The SeatLock model: temporary seat locking](#12-the-seatlock-model-temporary-seat-locking)

---

## 1. Key folder structure

```
app/laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/         ← CineController, PeliculaController, ReservaController...
│   │   │   └── Auth/            ← AuthenticatedSessionController (login/logout)
│   │   └── Middleware/
│   │       ├── IsAdmin.php      ← Access lock: 'admin' role only
│   │       └── CanManage.php    ← Access lock: 'admin' or 'taquilla' roles
│   └── Models/
│       ├── User.php             ← User + isAdmin(), isTaquilla(), canManage() methods
│       ├── Cine.php             ← has many Salas
│       ├── Sala.php             ← belongs to a Cine, has many Sessions
│       ├── Pelicula.php         ← has many Sessions and many Categorias (N:M)
│       ├── Categoria.php        ← many Peliculas (N:M)
│       ├── Sesion.php           ← belongs to a Sala and a Pelicula
│       └── Reserva.php          ← belongs to a User and a Sesion
├── database/
│   ├── migrations/              ← define the table structure
│   └── seeders/                 ← seed data (users, cinemas, categories...)
├── resources/views/             ← all the HTML (Blade)
│   ├── layout.blade.php         ← base template (nav, head, footer)
│   ├── auth/dashboard.blade.php ← admin/taquilla dashboard
│   ├── cliente/dashboard.blade.php ← client dashboard
│   ├── usuarios/
│   │   ├── misReservas.blade.php
│   │   └── reservar.blade.php
│   └── peliculas/ cines/ salas/ sesiones/ reservas/ usuarios/
│       └── index, show, create, edit for each entity
└── routes/
    └── web.php                  ← all the application's URLs
```

---

## 2. The database: our 8 tables

### Real relationship diagram

```
cines ──1:N──► salas ──1:N──► sesions ──1:N──► reservas ◄──N:1── users
                                  ▲
                                N:1
                              peliculas ◄──N:M──► categorias
                                           (pelicula_categoria)
```

### The tables

| Table | Columns |
|---|---|
| `users` | `id, name, apellidos, email, password, rol, telefono, created_at` |
| `cines` | `id, nombre, direccion_completa, ciudad, provincia` |
| `salas` | `id, nombre, capacidad, disposicion_butacas, fk_cine_id` |
| `peliculas` | `id, titulo, sinopsis, duracion_min, classificacio_edad, trailer_url` |
| `categorias` | `id, nombre` |
| `pelicula_categoria` | `fk_pelicula_id, fk_categoria_id` ← pivot table, no id of its own |
| `sesions` | `id, fk_sala_id, fk_pelicula_id, fecha_hora, preu_base` |
| `reservas` | `id, fk_usuario_id, fk_sesion_id, butaques_seleccionades, total_pagat, estat` |

**Why are the foreign keys called `fk_*`?** It's our own convention. Laravel doesn't require it, but it makes it very clear when reading the code which field is a foreign key.

**Why is the table called `sesions` and not `sesiones`?** Because that's how it was defined in the migration. The `Sesion` model states it explicitly with `protected $table = 'sesions'` so Laravel doesn't try to look for the `sesiones` table (which doesn't exist).

---

## 3. The Models and their relationships

### `Cine.php` — has many salas

```php
// The cine doesn't know its own id within salas — the sala knows it via fk_cine_id
public function salas(): HasMany
{
    return $this->hasMany(Sala::class, 'fk_cine_id');
    // SQL: SELECT * FROM salas WHERE fk_cine_id = [this cine's id]
}
```

**Real usage in the project** — in `salas/create.blade.php`:
```blade
<select name="fk_cine_id">
    @foreach($cines as $cine)
        <option value="{{ $cine->id }}">{{ $cine->nombre }}</option>
    @endforeach
</select>
```

---

### `Sala.php` — belongs to a cine, has many sessions

```php
// Inverse side: the sala does know fk_cine_id
public function cine(): BelongsTo
{
    return $this->belongsTo(Cine::class, 'fk_cine_id');
    // SQL: SELECT * FROM cines WHERE id = [this sala's fk_cine_id value]
}

public function sesiones(): HasMany
{
    return $this->hasMany(Sesion::class, 'fk_sala_id');
}

// Lets us reach reservas without going through sesions manually
public function reservas(): HasManyThrough
{
    return $this->hasManyThrough(Reserva::class, Sesion::class, 'fk_sala_id', 'fk_sesion_id');
}
```

---

### `Pelicula.php` — the most complex one (N:M + hasManyThrough)

```php
// Direct relationship: a movie has many sessions
public function sesiones(): HasMany
{
    return $this->hasMany(Sesion::class, 'fk_pelicula_id');
}

// N:M relationship with categories — needs the pelicula_categoria pivot table
public function categorias(): BelongsToMany
{
    return $this->belongsToMany(
        Categoria::class,
        'pelicula_categoria',  // pivot table name
        'fk_pelicula_id',      // FK column pointing to peliculas
        'fk_categoria_id'      // FK column pointing to categorias
    );
    // SQL: SELECT categorias.* FROM categorias
    //      INNER JOIN pelicula_categoria ON categorias.id = pelicula_categoria.fk_categoria_id
    //      WHERE pelicula_categoria.fk_pelicula_id = [this movie's id]
}

// Movie's reservations: there's no direct FK between peliculas and reservas,
// so we take the path: pelicula → sesion → reserva
public function reservas(): HasManyThrough
{
    return $this->hasManyThrough(
        Reserva::class,     // what I want to end up with
        Sesion::class,      // what I join through
        'fk_pelicula_id',   // FK from pelicula to sesions
        'fk_sesion_id'      // FK from sesion to reservas
    );
}
```

**Real usage** — in `PeliculaController@store`:
```php
// We pull categories out of the validated array because Pelicula::create() doesn't accept them
$categorias = $validated['categorias'] ?? [];
unset($validated['categorias']);

$pelicula = Pelicula::create($validated);

// attach() inserts rows into pelicula_categoria
if (!empty($categorias)) {
    $pelicula->categorias()->attach($categorias);
}
```

And in `PeliculaController@update`, we swap `attach` for `sync`:
```php
$pelicula->categorias()->sync($request->categorias ?? []);
// sync() removes the old categories and inserts the new ones
// attach() only inserts (it would create duplicates if used on update)
```

---

### `Sesion.php` — the bridge between sala and pelicula

```php
protected $table = 'sesions'; // explicit table name

protected function casts(): array
{
    return [
        'fecha_hora' => 'datetime', // converts "2026-03-15 19:30:00" → a Carbon object
        'preu_base'  => 'decimal:2',
    ];
}

public function sala(): BelongsTo    { return $this->belongsTo(Sala::class, 'fk_sala_id'); }
public function pelicula(): BelongsTo { return $this->belongsTo(Pelicula::class, 'fk_pelicula_id'); }
public function reservas(): HasMany  { return $this->hasMany(Reserva::class, 'fk_sesion_id'); }
```

**Why the cast to `datetime`?** Because MySQL stores `fecha_hora` as text (`"2026-03-15 19:30:00"`). With the cast, `$sesion->fecha_hora` is a Carbon object and we can do:
```php
$sesion->fecha_hora->format('d/m/Y H:i')  // "15/03/2026 19:30"
$sesion->fecha_hora->gt(now())             // true if the session is in the future
```

Without the cast, `$sesion->fecha_hora->format(...)` would throw an error because it would just be a string.

---

### `Reserva.php` — the central entity

```php
protected $fillable = [
    'fk_usuario_id',
    'fk_sesion_id',
    'butaques_seleccionades',  // e.g.: "A1, A2, B5"
    'total_pagat',
    'estat',                   // 'pendent' | 'pagat' | 'cancelat'
];

public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'fk_usuario_id'); }
public function sesion(): BelongsTo  { return $this->belongsTo(Sesion::class, 'fk_sesion_id'); }
```

**The `estat` field** is an `ENUM` in the database. At the code level we validate it with:
```php
'estat' => 'required|in:pendent,pagat,cancelat'
```

---

### `User.php` — user + role logic

```php
protected $fillable = ['name', 'apellidos', 'telefono', 'rol', 'email', 'password'];

public function reservas(): HasMany
{
    return $this->hasMany(Reserva::class, 'fk_usuario_id');
}

// Helper methods used throughout the codebase
public function isAdmin(): bool     { return $this->rol === 'admin'; }
public function isTaquilla(): bool  { return $this->rol === 'taquilla'; }
public function canManage(): bool   { return in_array($this->rol, ['admin', 'taquilla']); }
```

These methods are used directly in the views:
```blade
@if(auth()->user()->isAdmin())
    <a href="{{ route('usuarios.index') }}">Manage Users</a>
@endif
```

And in the `IsAdmin` middleware:
```php
if (!Auth::check() || !Auth::user()->isAdmin()) {
    abort(403, 'Access denied.');
}
```

---

## 4. The Routes: `routes/web.php`

```php
// GROUP 1 — isAdmin: admin-only
// (cinema structure: catalog, salas, users)
Route::middleware(['auth', 'verified', 'isAdmin'])->group(function () {
    Route::resource('usuarios', UserController::class);
    Route::resource('peliculas', PeliculaController::class)->except(['index', 'show']);
    Route::resource('salas',    SalaController::class);
    Route::resource('cines',    CineController::class)->except(['index', 'show']);
});

// GROUP 2 — canManage: admin + taquilla
// (day-to-day operations: sessions and reservations)
Route::middleware(['auth', 'verified', 'canManage'])->group(function () {
    Route::resource('sesiones', SesionController::class)->except(['index', 'show']);

    // except 'store': clients also create reservations (via the auth group)
    // except 'show':  clients can see the detail of their own reservations
    Route::resource('reservas', ReservaController::class)->except(['store', 'show']);
});

// GROUP 3 — auth: any logged-in user
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn() => view('auth.dashboard'))->name('dashboard');

    // Client dashboard
    Route::get('/dashboard/client', function () {
        $reservas = auth()->user()->reservas()
            ->with('sesion.pelicula', 'sesion.sala')->latest()->take(5)->get();
        $total = auth()->user()->reservas()->count();
        return view('cliente.dashboard', compact('reservas', 'total'));
    })->name('cliente.dashboard');

    // Client-specific routes
    Route::get('/mis-reservas', [ReservaController::class, 'misReservas'])->name('reservas.mis');
    Route::get('/reservar',     [ReservaController::class, 'reservar'])->name('reservas.form');
    Route::delete('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

    // Reservations: detail (show) and creation (store) accessible to all roles
    Route::get('/reservas/{reserva}', [ReservaController::class, 'show'])->name('reservas.show');
    Route::post('/reservas',          [ReservaController::class, 'store'])->name('reservas.store');

    Route::resource('peliculas', PeliculaController::class)->only(['index', 'show']);
    Route::resource('sesiones',  SesionController::class)->only(['index', 'show']);
    Route::resource('cines',     CineController::class)->only(['index', 'show']);
});
```

### Why three groups instead of two?

There used to be two groups (`isAdmin` and `auth`), which left `taquilla` unable to manage anything beyond its own reservations. With three groups:

| Group | Middleware | Who can access | Responsibility |
|---|---|---|---|
| 1 | `isAdmin` | admin | Cinema structure (catalog, salas, users) |
| 2 | `canManage` | admin + taquilla | Daily operations (sessions, reservations CRUD) |
| 3 | `auth` | everyone | Public reads + client actions |

### Why does the order of the groups matter?

`Route::resource('peliculas', ...)` registers `GET /peliculas/create`. If the `auth` group (which contains `->only(['index','show'])`) came first, Laravel would see `/peliculas/create` and interpret `create` as the value of the `{pelicula}` wildcard, calling `PeliculaController@show("create")` → 404 error.

By putting `isAdmin` first, the URL `/peliculas/create` falls into the correct group. The same logic applies to the `canManage` group with `/reservas/create`.

### Why do the client routes (`/mis-reservas`, `/reservar`) come **BEFORE** the resource routes?

If `Route::resource('reservas', ...)` (or the route `GET /reservas/{reserva}`) came first, `/reservas/cancelar` would be interpreted as `{reserva}` = `"cancelar"` → `ReservaController@show("cancelar")` → 404.

### `Route::resource` — the 7 routes it generates

When you write `Route::resource('peliculas', PeliculaController::class)`, Laravel automatically registers:

| HTTP Method | URL | Route name | Controller method |
|---|---|---|---|
| GET | `/peliculas` | `peliculas.index` | `index()` |
| GET | `/peliculas/create` | `peliculas.create` | `create()` |
| POST | `/peliculas` | `peliculas.store` | `store()` |
| GET | `/peliculas/{pelicula}` | `peliculas.show` | `show($id)` |
| GET | `/peliculas/{pelicula}/edit` | `peliculas.edit` | `edit($id)` |
| PUT/PATCH | `/peliculas/{pelicula}` | `peliculas.update` | `update()` |
| DELETE | `/peliculas/{pelicula}` | `peliculas.destroy` | `destroy()` |

`->only(['index','show'])` limits it to just the first two plus the fourth.
`->except(['store', 'show'])` excludes those and registers the rest.

---

## 5. The access-control Middlewares

### `IsAdmin.php` — admin-only

```php
// app/Http/Middleware/IsAdmin.php
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Access denied. Only administrators can access this section.');
        }
        return $next($request); // continue to the controller
    }
}
```

Protects management of the cinema's structure: users, movies, salas, and cinemas. **Taquilla has no access** to any of these resources.

### `CanManage.php` — admin and taquilla

```php
// app/Http/Middleware/CanManage.php
class CanManage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->canManage()) {
            abort(403, 'Access denied. This section is exclusive to administrators and box-office staff.');
        }
        return $next($request);
    }
}
```

Protects day-to-day operational management. Uses the `canManage()` method on the User model:
```php
public function canManage(): bool
{
    return in_array($this->rol, ['admin', 'taquilla']);
}
```

**Registration in `bootstrap/app.php`:**
```php
$middleware->alias([
    'isAdmin'   => IsAdmin::class,
    'canManage' => CanManage::class,
]);
```

Without this registration, writing `'canManage'` in the routes would throw an error. The alias is the short name we use in `routes/web.php`.

---

## 6. The Controllers

### `ReservaController` — the most complete one

It has two sets of methods:

**1. CRUD managed by admin/taquilla (protected by `canManage` at the routes level):**

```php
public function index()
{
    // Loads ALL reservations with their relationships
    // with('usuario', 'sesion') → does 3 queries instead of 1+N
    // The canManage middleware already guarantees no client can reach here
    $reservas = Reserva::with('usuario', 'sesion')->get();
    return view('reservas.index', compact('reservas'));
}

public function show(string $id)
{
    // sesion.pelicula and sesion.sala → loads the session + its movie + its sala
    $reserva = Reserva::with('usuario', 'sesion.pelicula', 'sesion.sala')->findOrFail($id);

    // The only role check in the controller: show is accessible to everyone (auth group),
    // but a client should only be able to see their own reservations
    if (auth()->user()->rol === 'cliente' && $reserva->fk_usuario_id !== auth()->id()) {
        abort(403);
    }

    return view('reservas.show', compact('reserva'));
}

public function destroy(string $id)
{
    $reserva = Reserva::findOrFail($id); // automatic 404 if it doesn't exist
    $reserva->delete();
    return redirect()->route('reservas.index')->with('success', '...');
}
```

**Why is `show` the only method with a role check in the controller?** Because it's the only case where the route is accessible to all roles (`auth` group) but the resource belongs to a specific user. All other methods are protected directly by route middleware (`canManage`), so the controller doesn't need any extra checking — that's the **Separation of Concerns** principle.

**2. Client-flow methods:**

```php
public function misReservas()
{
    // auth()->user() → the logged-in user
    // ->reservas() → uses the HasMany relationship on the User model
    // ->with('sesion.pelicula', 'sesion.sala') → loads nested relationships
    // ->latest() → ORDER BY created_at DESC
    // ->paginate(10) → LIMIT 10 + generates pagination links
    $reservas = auth()->user()->reservas()
        ->with('sesion.pelicula', 'sesion.sala')
        ->latest()
        ->paginate(10);
    return view('usuarios.misReservas', compact('reservas'));
}

public function reservar()
{
    // Only future sessions: where('fecha_hora', '>', now())
    $sesiones = Sesion::with('pelicula', 'sala')
        ->where('fecha_hora', '>', now())
        ->orderBy('fecha_hora')
        ->get();
    return view('usuarios.reservar', compact('sesiones'));
}

public function cancelar(Reserva $reserva) // ← Route Model Binding: Laravel does findOrFail automatically
{
    // Double security: the auth middleware already checks the user is logged in
    // Here we check that the reservation DOES belong to whoever wants to cancel it
    if (auth()->id() !== $reserva->fk_usuario_id && auth()->user()->rol !== 'admin') {
        abort(403, 'Access denied.'); // HTTP 403 Forbidden
    }

    if ($reserva->estat !== 'pendent') {
        return back()->with('error', 'Only reservations in "pendent" status can be cancelled.');
    }

    $reserva->update(['estat' => 'cancelat']);
    return redirect()->route('reservas.mis')->with('status', 'Reservation cancelled.');
}
```

**Route Model Binding** in `cancelar(Reserva $reserva)`: when the URL is `/reservas/7/cancelar`, Laravel sees the parameter is named `{reserva}` and typed as `Reserva` → it does `Reserva::findOrFail(7)` automatically. If it doesn't exist, it returns a 404 without us writing any code for it.

---

### `PeliculaController` — the `attach` vs `sync` complexity

```php
public function store(Request $request)
{
    $validated = $request->validate([...]);

    // Categories are NOT a column on peliculas, they're the N:M relationship
    // If we did Pelicula::create($validated) with 'categorias' included → ERROR
    $categorias = $validated['categorias'] ?? [];
    unset($validated['categorias']); // remove categories from the array

    $pelicula = Pelicula::create($validated); // now it's safe, no categories

    if (!empty($categorias)) {
        $pelicula->categorias()->attach($categorias);
        // INSERT INTO pelicula_categoria (fk_pelicula_id, fk_categoria_id) VALUES (...)
    }
}

public function update(Request $request, string $id)
{
    $pelicula = Pelicula::findOrFail($id);
    // ...validate...
    $pelicula->update($validated);

    // sync() instead of attach() because we're editing, not creating
    // sync() = deletes the current rows in pelicula_categoria for this movie
    //          + inserts the new ones
    $pelicula->categorias()->sync($request->categorias ?? []);
}
```

---

### `AuthenticatedSessionController` — redirect by role

```php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();           // verifies email + password
    $request->session()->regenerate();  // prevents session fixation attacks

    $destination = Auth::user()->rol === 'cliente'
        ? route('cliente.dashboard')   // → /dashboard/client
        : route('dashboard');          // → /dashboard (admin + taquilla)

    // intended() → if the user was trying to reach /peliculas/3 but wasn't logged in,
    //              it sends them there. Otherwise it uses $destination.
    return redirect()->intended($destination);
}
```

---

## 7. The Role system

### Possible values for `users.rol`

Defined as an `ENUM` in the migration `2026_01_11_000003_add_lumiere_fields_to_users_table.php`:
```php
$table->enum('rol', ['admin', 'taquilla', 'cliente'])->default('cliente');
```

The ENUM guarantees at the database level that no other value can get in. On top of that, we validate it in the controller:
```php
// UserController@store and @update
'rol' => 'required|in:admin,taquilla,cliente'
```

### The project's actual permissions matrix

| Route | admin | taquilla | cliente |
|---|---|---|---|
| `GET /` (home) | ✅ | ✅ | ✅ |
| `GET /peliculas` | ✅ | ✅ | ✅ |
| `GET /sesiones` | ✅ | ✅ | ✅ |
| `GET /cines` | ✅ | ✅ | ✅ |
| `POST /peliculas` (create) | ✅ | ❌ | ❌ |
| `DELETE /peliculas/{id}` | ✅ | ❌ | ❌ |
| `POST/PUT /sesiones` (manage) | ✅ | ✅ | ❌ |
| `GET /usuarios` | ✅ | ❌ | ❌ |
| `GET /reservas` (all) | ✅ | ✅ | ❌ |
| `GET /reservas/{id}` (detail) | ✅ | ✅ | ✅* |
| `POST /reservas` (create) | ✅ | ✅ | ✅ |
| `PUT /reservas/{id}` (edit) | ✅ | ✅ | ❌ |
| `DELETE /reservas/{id}` (delete) | ✅ | ✅ | ❌ |
| `GET /mis-reservas` | ✅ | ✅ | ✅ |
| `DELETE /reservas/{r}/cancelar` | ✅ | ✅** | ✅** |

\* The controller checks that the client can only see their own reservations (403 if it belongs to another user)
\** Only their own reservation, or if they're admin/taquilla

---

## 8. The Blade Views

All views extend `resources/views/layout.blade.php`:

```blade
@extends('layout')          ← inherits the base layout
@section('title', 'Title')  ← fills the <title> in the head
@section('content')
    <!-- your HTML here -->
@endsection
```

### The reservation-creation form (client) — `usuarios/reservar.blade.php`

```blade
<form method="POST" action="{{ route('reservas.store') }}">
    @csrf   ← anti-CSRF token, Laravel checks it automatically on every POST

    {{-- The form must not let the user pick a different user --}}
    <input type="hidden" name="fk_usuario_id" value="{{ auth()->id() }}">
    <input type="hidden" name="estat" value="pendent">

    <select name="fk_sesion_id">
        @foreach($sesiones as $sesion)
            <option value="{{ $sesion->id }}">
                {{ $sesion->pelicula->titulo }} | {{ $sesion->sala->nombre }} |
                {{ $sesion->fecha_hora->format('d/m/Y H:i') }} – {{ $sesion->preu_base }}€
            </option>
        @endforeach
    </select>

    <input type="text" name="butaques_seleccionades" value="{{ old('butaques_seleccionades') }}">
    {{--                                                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
        old() restores the value if the form failed validation,
        so the user doesn't have to retype it --}}
</form>
```

### Cancelling a reservation in `usuarios/misReservas.blade.php`

```blade
@if($reserva->estat === 'pendent')
    <form action="{{ route('reservas.cancelar', $reserva) }}" method="POST"
          onsubmit="return confirm('Cancel this reservation?')">
        @csrf
        @method('DELETE')
        {{-- @method('DELETE') adds <input type="hidden" name="_method" value="DELETE">
             because HTML forms don't support DELETE natively --}}
        <button type="submit">Cancel</button>
    </form>
@endif
```

### Showing validation errors

```blade
<input type="text" name="titulo" value="{{ old('titulo') }}" class="form-input">
@error('titulo')
    <p class="form-error">{{ $message }}</p>
    {{-- $message holds the error text --}}
@enderror
```

When `validate()` fails, Laravel automatically redirects back and stores the errors in the session. `@error('titulo')` checks whether there's an error for the `titulo` field and shows it.

---

## 9. Validation for each form

### `ReservaController` — the strictest one, because it deals with money

```php
$request->validate([
    'fk_usuario_id'          => 'required|integer|exists:users,id',
    //                                                ^^^^^^^^^^^^
    //   exists:users,id does → SELECT COUNT(*) FROM users WHERE id = [value]
    //   If it doesn't exist → validation error (prevents reservations for ghost users)

    'fk_sesion_id'           => 'required|integer|exists:sesions,id',
    //   Checks the session exists in the 'sesions' table (not 'sesiones'!)

    'butaques_seleccionades' => 'required|string|max:500',
    'total_pagat'            => 'required|numeric|min:0',
    'estat'                  => 'required|in:pendent,pagat,cancelat',
]);
```

### `PeliculaController` — categories are optional

```php
$request->validate([
    'titulo'             => 'required|string|max:255',
    'sinopsis'           => 'nullable|string',       // nullable → can be empty
    'duracion_min'       => 'required|integer|min:1',
    'classificacio_edad' => 'nullable|string|max:10',
    'trailer_url'        => 'nullable|url|max:255',  // url → validates http(s):// format
    'categorias'         => 'nullable|array',
    'categorias.*'       => 'exists:categorias,id',
    //                  ↑
    //   .* applies the rule to EACH ELEMENT of the array
    //   Prevents sending a category ID that doesn't exist
]);
```

### `UserController` — email uniqueness

```php
// On create:
'email' => 'required|email|unique:users,email'
// unique:users,email → SELECT COUNT(*) FROM users WHERE email = [value]
// If it already exists → "The email has already been taken." error

// On edit (we need to ignore the user's own record):
'email' => 'required|email|unique:users,email,' . $id
//                                              ^^^^^^
//   Ignores the record with id=$id
//   Otherwise, editing user 5 would fail because their own email "already exists"
```

---

## 10. Migrations: order matters

Migrations run in chronological order. The filename includes the date:

```
0001_01_01_000000_create_users_table.php          ← Breeze (don't touch)
2026_01_11_000003_add_lumiere_fields_to_users_table.php  ← adds rol, telefono, apellidos
2026_01_19_000004_create_reservas_table.php        ← reservas (no session FK yet!)
2026_01_20_000000_create_peliculas_table.php
2026_01_20_000001_create_categorias_table.php
2026_01_20_000002_create_pelicula_categoria_table.php    ← N:M pivot
2026_01_22_083545_create_sesins_table.php
2026_02_07_213514_create_salas_table.php
2026_02_25_000001_add_fk_sesion_to_reservas_table.php    ← now we add the FK
2026_02_25_000002_create_cines_table.php
2026_02_25_000003_add_fk_cine_to_salas_table.php
```

**Why is `reservas` created WITHOUT the session FK, and it's added later?** Because the `sesions` table didn't exist yet when `reservas` was created. You can't add an FK pointing at a table that doesn't exist. The fix: create `reservas` first (without the FK), create `sesins`, and then add the FK with a separate migration.

Example of the migration that adds the FK:
```php
// 2026_02_25_000001_add_fk_sesion_to_reservas_table.php
public function up(): void
{
    Schema::table('reservas', function (Blueprint $table) {
        $table->foreignId('fk_sesion_id')
              ->nullable()
              ->constrained('sesins')   // FK → sesins.id
              ->onDelete('set null');   // if the session is deleted, the reservation is left with NULL
    });
}
```

---

## 11. The 3-step purchase flow

`CompraController` is the controller that manages the ticket-purchase process. It doesn't follow the resource structure (there's no `index`, `show`, `create`, `edit`...) because it doesn't manage a resource, it manages a **sequential flow**.

### The flow's routes

```php
// routes/web.php — auth group (any logged-in user)
Route::get('/comprar',          [CompraController::class, 'step1'])->name('compra.step1');
Route::post('/comprar/step1',   [CompraController::class, 'step1Store'])->name('compra.step1.store');
Route::get('/comprar/step2',    [CompraController::class, 'step2'])->name('compra.step2');
Route::post('/comprar/step2',   [CompraController::class, 'step2Store'])->name('compra.step2.store');
Route::get('/comprar/step3',    [CompraController::class, 'step3'])->name('compra.step3');
Route::post('/comprar/step3',   [CompraController::class, 'step3Store'])->name('compra.step3.store');
Route::post('/comprar/cancel',  [CompraController::class, 'cancel'])->name('compra.cancel');
Route::post('/comprar/lock-seat',   [CompraController::class, 'lockSeat'])->name('compra.lockSeat');
Route::post('/comprar/unlock-seat', [CompraController::class, 'unlockSeat'])->name('compra.unlockSeat');
```

### How data flows between steps

The three steps don't communicate via URL (there's no `?sesion_id=3` between steps, because it would be easy to tamper with). We use **Laravel's session**, which is a memory space tied to the browser's cookie but stored on the server:

```
Step 1 GET  /comprar?sesion_id=5
  └─ Shows the ticket-type form (adult, reduced, family, senior)
  └─ POST /comprar/step1
       └─ Validates and saves to session('compra'):
          { sesion_id: 5, entrades: {adult:2, jubilat:1}, num_entrades: 3, preu_total: 18.50 }
       └─ Redirect → GET /comprar/step2

Step 2 GET  /comprar/step2
  └─ Reads session('compra') to know how many seats need to be selected
  └─ Shows the visual grid of the sala with free/taken/locked seats
  └─ POST /comprar/step2
       └─ Saves the chosen seats to session('compra')
       └─ Redirect → GET /comprar/step3

Step 3 GET  /comprar/step3
  └─ Shows the personal-details and payment-method form
  └─ POST /comprar/step3
       └─ Creates the Reserva in the DB (status: 'pendent')
       └─ Clears session('compra')
       └─ Redirect → confirmation
```

### Ticket types and price calculation

The types are defined as a constant on the controller:

```php
const TIPUS = [
    'adult'   => ['label' => 'Adulto',        'desc' => '',                          'factor' => 1.00],
    'reduit'  => ['label' => 'Reducida',       'desc' => 'Menors 14 / Carnet jove',  'factor' => 0.80],
    'familia' => ['label' => 'Família',        'desc' => 'Preu per persona',          'factor' => 0.82],
    'jubilat' => ['label' => 'Sènior +65',     'desc' => '',                          'factor' => 0.70],
];
```

The final price of each ticket is `preu_base_sessio × factor`. If the session's price is 9.00 €:
- Adult: 9.00 × 1.00 = **9.00 €**
- Reduced: 9.00 × 0.80 = **7.20 €**
- Senior: 9.00 × 0.70 = **6.30 €**

### The `ClearPurchaseSession` middleware

There's a middleware that detects when the user abandons the purchase flow (navigates to a route that isn't `/comprar/...`) and automatically clears the session data and the locks:

```php
// app/Http/Middleware/ClearPurchaseSession.php
public function handle(Request $request, Closure $next): Response
{
    $isCompraRoute = str_starts_with($request->path(), 'comprar');

    // If the user leaves the purchase flow, we clear the session data
    // and release any seats they had locked
    if (! $isCompraRoute && session()->has('compra')) {
        app(CompraController::class)->releaseLocks();
        session()->forget('compra');
    }

    return $next($request);
}
```

---

## 12. The SeatLock model: temporary seat locking

### What is it for?

Imagine two users viewing the same session at the same time, and both select seat A5. Without any locking mechanism, both would reach step 3 and both would create a reservation for A5. That's the **race condition** problem.

The `SeatLock` model solves this: when a user selects a seat in step 2, a record is created in the `seat_locks` table that "reserves" that seat for a few minutes. If another user tries to select the same seat, they see it as locked (yellow in the grid).

### The `seat_locks` table structure

```
seat_locks
───────────────────────────────────────────────────────
id            | int   | PK autoincrement
sesion_id     | int   | FK → sesins.id
butaca        | str   | e.g.: "A5", "B12"
user_id       | int   | FK → users.id (nullable if not logged in)
session_token | str   | browser session token (alternative to user_id)
expires_at    | datetime | when the lock expires
```

### How the lock works

```php
// CompraController@lockSeat — called via AJAX from step 2's JS
public function lockSeat(Request $request): JsonResponse
{
    $request->validate([
        'sesion_id' => 'required|integer|exists:sesins,id',
        'butaca'    => 'required|string|max:10',
    ]);

    // First we check whether someone else already has it locked
    if (SeatLock::isLockedByOther($request->sesion_id, $request->butaca, auth()->id(), session()->getId())) {
        return response()->json(['locked' => true], 409); // 409 Conflict
    }

    // Create or update the lock (upsert: insert if it doesn't exist, update if it does)
    SeatLock::updateOrCreate(
        ['sesion_id' => $request->sesion_id, 'butaca' => $request->butaca,
         'user_id' => auth()->id(), 'session_token' => session()->getId()],
        ['expires_at' => now()->addMinutes(8)] // 8-minute window
    );

    return response()->json(['locked' => false]);
}
```

### The `isLockedByOther` method

```php
// SeatLock.php
public static function isLockedByOther(int $sesionId, string $butaca, ?int $userId, ?string $token): bool
{
    static::clearExpired(); // first clear out the ones that have already expired

    $lock = static::where('sesion_id', $sesionId)
        ->where('butaca', $butaca)
        ->where('expires_at', '>=', now()) // only currently active locks
        ->first();

    if (! $lock) return false;              // no lock → free
    if ($userId && $lock->user_id === $userId) return false; // it's this same user
    if ($token && $lock->session_token === $token) return false; // it's this same session

    return true; // someone else has the seat locked
}
```

### Why use `session_token` in addition to `user_id`?

Because if the user isn't logged in (browsing as a guest), `user_id` is `null` and we can't identify them by ID. `session_token` is the unique identifier of the browser's HTTP session, which exists for everyone regardless of whether they've logged in or not.

### The visual seat grid (step 2)

The `compra/step2.blade.php` view receives from the controller:
- `$takenSeats` — seats with a confirmed reservation (in red, `#dc2626`)
- `$lockedSeats` — seats locked by other users (in yellow, `#f59e0b`)
- `$myLocks` — seats the current user has locked (in green, `#16a34a`)

The grid is generated with PHP on the server using the sala's layout (`$layout['rows']` and `$layout['seatsPerRow']`). Every seat button calls `lockSeat()` or `unlockSeat()` via JavaScript (fetch/AJAX) without reloading the page.

---

## 13. Scalability and Long-Term Maintenance

### Current state: up to ~500 concurrent users

The current architecture (MySQL + `lockForUpdate()` + `seat_locks` table) works fine for a cinema with normal traffic. The pessimistic lock lasts milliseconds and temporary locks expire after 8 minutes.

### Scaling plan if volume grows (>5,000 concurrent users)

```
Level 1 (current):  MySQL locks + seat_locks table
                    ✅ Works fine up to ~500 concurrent users per session

Level 2 (medium):   Redis for seat locks (SETNX with TTL)
                    ✅ 10x faster than MySQL for ephemeral locks
                    ✅ Native TTL (no cleanup cron needed)
                    Change: SeatLock::updateOrCreate() → Redis::set()

Level 3 (high):     Redis + Queue Workers for purchases
                    ✅ Purchases get queued and processed sequentially
                    ✅ Eliminates race conditions entirely
                    Change: PurchaseService → DispatchJob → ProcessPurchase

Level 4 (scale):    Microservices (independent seats-service)
                    ✅ Horizontal scaling
                    ⚠️ High operational complexity
```

### Recurring maintenance required

| Task | Frequency | Command/Action |
|-------|------------|---------------|
| Clean up expired locks | Every minute (automatic) | `php artisan schedule:run` → `CleanupExpiredSeatLocks` |
| Sync TMDB listings | Every 6 hours (automatic) | `sesions:generate-from-releases` |
| Clean up past sessions | Daily (automatic) | `CleanPastSessions` |
| Renew TMDB key | Yearly | Update `TMDB_API_KEY` in `.env` |
| DB backup | Daily | `mysqldump` via cron or VPS service |
| Update dependencies | Monthly | `composer update` + `npm update` + tests |
| Review error logs | Weekly | `storage/logs/laravel.log` |

### Recommended monitoring

```bash
# Check that the scheduler is running:
tail -f storage/logs/laravel.log | grep -i "seat_lock\|schedule"

# Verify the TMDB cache is active:
php artisan tinker --execute="echo cache()->has('tmdb_now_playing') ? 'OK' : 'EMPTY';"

# Count active locks (should be ~0 outside peak hours):
php artisan tinker --execute="echo App\\Models\\SeatLock::where('expires_at','>',now())->count();"
```

---

*Technical documentation — CineFlow — M0616 — DAW*
