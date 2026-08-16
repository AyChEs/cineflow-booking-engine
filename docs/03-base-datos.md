# 📊 Database Schema

> Complete documentation of the relational model, table definitions, relationships, indexes, and DB conventions.

---

## Table of Contents

1. [Overview](#overview)
2. [Entity-Relationship Diagram (ER)](#entity-relationship-diagram-er)
3. [Table Definitions](#table-definitions)
4. [Eloquent Relationships](#eloquent-relationships)
5. [Indexes and Optimizations](#indexes-and-optimizations)
6. [Data Integrity](#data-integrity)
7. [Common Queries](#common-queries)

---

## Overview

The **CineFlow** database is normalized up to **3NF** (Third Normal Form), with:

- ✅ **9 main tables** modeling business entities
- ✅ **Many-to-many relationships** (movies ↔ categories)
- ✅ **Referential constraints** (FKs with cascade)
- ✅ **Indexes** for frequent queries
- ✅ **ACID transactions** on critical operations

### Statistics

| Metric | Value |
|---------|-------|
| Tables | 9 main + 3 auxiliary |
| Relationships | 15 defined relationships |
| Total columns | ~80 |
| Foreign Keys | 12 |
| Indexes | 25+ |

---

## Entity-Relationship Diagram (ER)

```
┌─────────────────────────────────────────────────────────────────────┐
│                           USERS                                     │
├─────────────┬──────────────┬──────────────┬────────────────────────┤
│ id (PK)     │ name         │ email        │ rol (admin/taq/client) │
│ password    │ apellidos    │ telefono     │ tarjeta_guardada       │
└─────────────┴──────┬───────┴──────────────┴────────────────────────┘
                     │
                     │ 1:M (One user → many bookings)
                     │
┌────────────────────▼──────────────────────────────────────────────┐
│                        BOOKINGS (reservas)                        │
├────────────┬──────────────┬────────────┬──────────┬──────────────┤
│ id (PK)    │ fk_usuario_id│ fk_sesion_id│ estado │ total_pagat  │
│ butaques   │ tipus_entrada │ created_at│ ticket_token           │
└────┬───────┴──────────────┴────┬──────┴──────────┴──────────────┘
     │                           │
     │                           │ 1:M (Screening → many bookings)
     │                           │
     │                    ┌──────▼────────────────────────────┐
     │                    │      SCREENINGS (sesiones)        │
     │                    ├─────────┬──────────┬──────────────┤
     │                    │ id (PK) │ fecha_hora│ preu_base   │
     │                    │ fk_sala_id│ fk_pelicula_id    │
     │                    └────┬────┴───┬──────┴──────────────┘
     │                         │        │
     │              ┌──────────┘        │
     │              │                   │
     │              │                   └─┐ 1:M (Movie → screenings)
     │              │                     │
     │              │           ┌─────────▼──────────────────┐
     │              │           │      MOVIES (películas)    │
     │              │           ├─────────┬──────────────────┤
     │              │           │ id (PK) │ titulo, sinopsis │
     │              │           │ poster_ │ duracion_min     │
     │              │           │ path    │ clasificacio_edad│
     │              │           └────┬────┴──────────────────┘
     │              │                │
     │              │       ┌───────┘ M:M relationship
     │              │       │
     │              │   ┌───▼────────────────────────────┐
     │              │   │   PELICULA_CATEGORIA (pivot)   │
     │              │   ├─────────────┬──────────────────┤
     │              │   │ fk_pelicula_│ fk_categoria_id  │
     │              │   │ id          │                  │
     │              │   └──────┬──────┴──────────────────┘
     │              │          │
     │              │          └─┐
     │              │            │
     │              │       ┌────▼────────────────┐
     │              │       │  CATEGORIES         │
     │              │       ├────────┬────────────┤
     │              │       │ id (PK)│ nombre     │
     │              │       │ slug   │            │
     │              │       └────────┴────────────┘
     │              │
     │      ┌───────▼──────────────────────────┐
     │      │      SCREENS (salas)             │
     │      ├─────────┬───────────┬────────────┤
     │      │ id (PK) │ fk_cine_id│ num_sala   │
     │      │ filas   │ asientos_por_fila      │
     │      └────┬────┴─────┬─────┴────────────┘
     │           │          │
     │     ┌─────▼──┐    ┌──▼──────────────────┐
     │     │ CINEMAS│    │ SEAT_LOCKS (temp)   │
     │     │ (cines)│    ├──────────┬──────────┤
     │     ├──────┬─┤    │ fk_sesion│ butaca  │
     │     │ id   │ │    │ expires_a│ usuario │
     │     │ nombre│    └──────────┴──────────┘
     │     └──────┴─┘
     │
     └──┐ 1:1 (Booking → BookingSeat)
        │
    ┌───▼──────────────────────┐
    │   RESERVA_SEATS          │
    ├────────┬──────────┬──────┤
    │ id (PK)│ fk_reserva│ seat │
    │ row    │ col      │      │
    └────────┴──────────┴──────┘
```

---

## Table Definitions

### 1. Table `users`

**Purpose**: Store user information (customers, admin, box office)

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    apellidos VARCHAR(255) NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    rol ENUM('admin', 'taquilla', 'cliente', 'guest') 
        DEFAULT 'cliente' COMMENT 'Role-based access control',
    tarjeta_guardada VARCHAR(255) NULL COMMENT 'Encrypted card (if applicable)',
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key fields**:
- `rol`: Access control (admin > taquilla > cliente > guest)
- `tarjeta_guardada`: Security — encrypted with Laravel's APP_KEY
- `email_verified_at`: NULL if the email hasn't been verified

---

### 2. Table `peliculas`

**Purpose**: Movie catalog (synced with TMDB)

```sql
CREATE TABLE peliculas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    sinopsis LONGTEXT,
    duracion_min INT UNSIGNED COMMENT 'Duration in minutes',
    classificacio_edad VARCHAR(10) COMMENT 'G, PG, PG-13, R, NC-17',
    poster_path VARCHAR(500) NULL COMMENT 'URL or path to poster (TMDB or local)',
    trailer_url VARCHAR(500) NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_titulo (titulo),
    FULLTEXT INDEX ft_titulo_sinopsis (titulo, sinopsis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key fields**:
- `poster_path`: Can be:
  - Relative TMDB path: `/9M82HW1vFJrSGpNNQqwLVOr0FKm.jpg`
  - Local path: `posters/2026/04/archivo.jpg`
  - Full URL: `https://image.tmdb.org/...`
- `classificacio_edad`: For age-based filtering

---

### 3. Table `categorias`

**Purpose**: Genres (action, drama, comedy, etc.)

```sql
CREATE TABLE categorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL COMMENT 'Action, Drama, Comedy, etc.',
    slug VARCHAR(100) UNIQUE NOT NULL COMMENT 'For friendly URLs',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4. Table `pelicula_categoria` (Pivot/Junction)

**Purpose**: Many-to-many relationship between movies and categories

```sql
CREATE TABLE pelicula_categoria (
    fk_pelicula_id BIGINT UNSIGNED NOT NULL,
    fk_categoria_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    
    PRIMARY KEY (fk_pelicula_id, fk_categoria_id),
    FOREIGN KEY (fk_pelicula_id) REFERENCES peliculas(id) 
        ON DELETE CASCADE,
    FOREIGN KEY (fk_categoria_id) REFERENCES categorias(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Sample data**:
```
pelicula_id | categoria_id
      5     |      1         (Movie 5 → Action)
      5     |      3         (Movie 5 → Sci-Fi)
      8     |      2         (Movie 8 → Drama)
```

---

### 5. Table `cines`

**Purpose**: Physical cinema locations

```sql
CREATE TABLE cines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL COMMENT 'CineFlow Barcelona',
    ciudad VARCHAR(100),
    direccion TEXT,
    telefono VARCHAR(20),
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_ciudad (ciudad),
    UNIQUE KEY uk_nombre_ciudad (nombre, ciudad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 6. Table `salas`

**Purpose**: Screens within a cinema (Screen 1, 2, 3...)

```sql
CREATE TABLE salas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_cine_id BIGINT UNSIGNED NOT NULL,
    num_sala INT COMMENT 'Screen number (1, 2, 3...)',
    filas INT UNSIGNED COMMENT 'Number of rows (A-Z)',
    asientos_por_fila INT UNSIGNED COMMENT 'Seats per row',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (fk_cine_id) REFERENCES cines(id) 
        ON DELETE CASCADE,
    INDEX idx_cine (fk_cine_id),
    UNIQUE KEY uk_cine_sala (fk_cine_id, num_sala)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example**: A screen with 12 rows (A-L) x 15 seats = 180 seats

---

### 7. Table `sesions`

**Purpose**: Screenings (the key piece: movie + screen + time)

```sql
CREATE TABLE sesions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_sala_id BIGINT UNSIGNED NOT NULL,
    fk_pelicula_id BIGINT UNSIGNED NOT NULL,
    fecha_hora DATETIME NOT NULL COMMENT 'When it screens',
    preu_base DECIMAL(8,2) NOT NULL COMMENT 'Base price (before discounts)',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (fk_sala_id) REFERENCES salas(id) 
        ON DELETE CASCADE,
    FOREIGN KEY (fk_pelicula_id) REFERENCES peliculas(id) 
        ON DELETE CASCADE,
    INDEX idx_fecha (fecha_hora),
    INDEX idx_pelicula (fk_pelicula_id),
    INDEX idx_sala (fk_sala_id),
    
    -- For fast lookups: "Screenings of movie X on date Y"
    UNIQUE KEY uk_sala_pelicula_fecha (
        fk_sala_id, fk_pelicula_id, DATE(fecha_hora)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 8. Table `reservas`

**Purpose**: Ticket purchase (the core transaction)

```sql
CREATE TABLE reservas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_usuario_id BIGINT UNSIGNED NULL COMMENT 'NULL for anonymous purchases',
    fk_sesion_id BIGINT UNSIGNED NOT NULL,
    
    -- Purchase data
    tipus_entrada VARCHAR(255) NOT NULL COMMENT 'adult, reduit, familia, jubilat (JSON)',
    butaques_seleccionades VARCHAR(500) NOT NULL COMMENT 'A1, A2, B5 (CSV)',
    total_pagat DECIMAL(10,2) NOT NULL,
    
    -- Status and validation
    estat ENUM(
        'pendiente', 
        'confirmada', 
        'cancelada', 
        'validada'
    ) DEFAULT 'pendiente',
    ticket_token VARCHAR(255) UNIQUE NULL COMMENT 'JWT for the QR code',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (fk_usuario_id) REFERENCES users(id) 
        ON DELETE SET NULL,     -- Keep bookings if the user is deleted
    FOREIGN KEY (fk_sesion_id) REFERENCES sesions(id) 
        ON DELETE CASCADE,
    
    INDEX idx_usuario (fk_usuario_id),
    INDEX idx_sesion (fk_sesion_id),
    INDEX idx_estado (estat),
    INDEX idx_token (ticket_token),
    INDEX idx_fecha_creacion (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key fields**:
- `fk_usuario_id`: NULL for purchases made without logging in (guest)
- `butaques_seleccionades`: "A1, A2, B5" — validated at the application level
- `types_entrada`: JSON with quantities {adult: 2, reduit: 1}
- `ticket_token`: JWT used to generate the QR code and validate the ticket

---

### 9. Table `seat_locks` (Temporary)

**Purpose**: Temporary seat lock during checkout (prevents overbooking)

```sql
CREATE TABLE seat_locks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_sesion_id BIGINT UNSIGNED NOT NULL,
    fk_usuario_id BIGINT UNSIGNED NULL,
    butaca VARCHAR(5) NOT NULL COMMENT 'A1, A2, Z15, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL COMMENT 'Lock expiration',
    
    FOREIGN KEY (fk_sesion_id) REFERENCES sesions(id) 
        ON DELETE CASCADE,
    FOREIGN KEY (fk_usuario_id) REFERENCES users(id) 
        ON DELETE SET NULL,
    
    -- Only one lock per seat per screening at a time
    UNIQUE KEY uk_sesion_butaca (fk_sesion_id, butaca),
    
    INDEX idx_expires (expires_at),
    INDEX idx_usuario (fk_usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Timeline**:
1. User A selects seat A1 → a SeatLock is created (expires_at = NOW() + 15 min)
2. User B tries A1 → REJECTED (already locked)
3. After 15 minutes without a completed purchase → automatic expiration
4. User C can now buy A1

---

### 10. Table `reserva_seats` (Normalization)

**Purpose**: Break out seats individually for normalized queries

```sql
CREATE TABLE reserva_seats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_reserva_id BIGINT UNSIGNED NOT NULL,
    row CHAR(1) COMMENT 'Row: A, B, C...',
    col INT UNSIGNED COMMENT 'Column: 1, 2, 3...',
    
    FOREIGN KEY (fk_reserva_id) REFERENCES reservas(id) 
        ON DELETE CASCADE,
    
    INDEX idx_reserva (fk_reserva_id),
    INDEX idx_fila_col (row, col)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Eloquent Relationships

### Model: `User`

```php
class User extends Authenticatable {
    public function reservas(): HasMany {
        return $this->hasMany(Reserva::class, 'fk_usuario_id');
    }
    
    public function isAdmin(): bool {
        return $this->rol === 'admin';
    }
}
```

### Model: `Pelicula`

```php
class Pelicula extends Model {
    // Many screenings
    public function sesiones(): HasMany {
        return $this->hasMany(Sesion::class, 'fk_pelicula_id');
    }
    
    // Many categories (M:M)
    public function categorias(): BelongsToMany {
        return $this->belongsToMany(
            Categoria::class, 'pelicula_categoria'
        );
    }
    
    // Access a movie's bookings through its screenings
    public function reservas(): HasManyThrough {
        return $this->hasManyThrough(
            Reserva::class, Sesion::class
        );
    }
    
    // Accessor for the poster URL
    public function getPosterUrlAttribute(): string { ... }
}
```

### Model: `Sesion`

```php
class Sesion extends Model {
    public function sala(): BelongsTo {
        return $this->belongsTo(Sala::class, 'fk_sala_id');
    }
    
    public function pelicula(): BelongsTo {
        return $this->belongsTo(Pelicula::class, 'fk_pelicula_id');
    }
    
    public function reservas(): HasMany {
        return $this->hasMany(Reserva::class, 'fk_sesion_id');
    }
}
```

### Model: `Reserva`

```php
class Reserva extends Model {
    protected $casts = [
        'total_pagat' => 'decimal:2',
    ];
    
    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, 'fk_usuario_id');
    }
    
    public function sesion(): BelongsTo {
        return $this->belongsTo(Sesion::class, 'fk_sesion_id');
    }
}
```

---

## Indexes and Optimizations

### Indexes for Common Lookups

```sql
-- 1. List movies in a category
CREATE INDEX idx_categoria_id 
    ON pelicula_categoria(fk_categoria_id);

-- 2. Upcoming screenings of a movie
CREATE INDEX idx_pelicula_fecha 
    ON sesions(fk_pelicula_id, fecha_hora);

-- 3. A user's bookings in the last month
CREATE INDEX idx_usuario_fecha 
    ON reservas(fk_usuario_id, created_at);

-- 4. Seats taken in a screening (for availability)
CREATE INDEX idx_sesion_estado 
    ON reservas(fk_sesion_id, estat);
```

### Full-Text Indexes for Search

```sql
-- Search movies by title or synopsis
ALTER TABLE peliculas 
    ADD FULLTEXT INDEX ft_busqueda (titulo, sinopsis);

-- Query:
SELECT * FROM peliculas 
WHERE MATCH(titulo, sinopsis) AGAINST('acción' IN BOOLEAN MODE);
```

---

## Data Integrity

### Foreign Key Constraints

```sql
-- Cascade: Deleting a movie deletes its screenings
ALTER TABLE sesions 
    ADD FOREIGN KEY (fk_pelicula_id) REFERENCES peliculas(id)
    ON DELETE CASCADE;

-- Set Null: Deleting a user leaves their bookings without a user
ALTER TABLE reservas 
    ADD FOREIGN KEY (fk_usuario_id) REFERENCES users(id)
    ON DELETE SET NULL;
```

### ACID Transactions

```php
// Example: Creating a booking (multiple inserts, all-or-nothing)
DB::transaction(function () {
    // 1. Create the booking
    $reserva = Reserva::create([...]);
    
    // 2. Create the seat detail rows
    foreach ($butaques as $butaca) {
        ReservaSeat::create([...]);
    }
    
    // 3. Release temporary locks
    SeatLock::where('butaca', $butaca)->delete();
    
    // If anything fails → automatic ROLLBACK
    // If everything is OK → automatic COMMIT
});
```

---

## Common Queries

### 1. List Movies with Available Screenings

```php
$peliculas = Pelicula::with([
    'sesiones' => fn($q) => $q
        ->where('fecha_hora', '>', now())
        ->with('sala.cine')
])
->whereHas('sesiones', fn($q) => $q->where('fecha_hora', '>', now()))
->paginate(12);
```

### 2. Available Seats in a Screening

```php
$takenSeats = Reserva::where('fk_sesion_id', $sesionId)
    ->pluck('butaques_seleccionades')
    ->flatten();

$lockedSeats = SeatLock::where('fk_sesion_id', $sesionId)
    ->where('expires_at', '>', now())
    ->pluck('butaca');

$available = collect($allSeats)
    ->diff($takenSeats)
    ->diff($lockedSeats);
```

### 3. A User's Bookings

```php
$reservas = User::find($userId)
    ->reservas()
    ->with('sesion.pelicula', 'sesion.sala.cine')
    ->where('estat', 'confirmada')
    ->orderByDesc('created_at')
    ->get();
```

### 4. Revenue by Movie

```php
$ingresos = Reserva::where('estat', 'confirmada')
    ->with('sesion.pelicula')
    ->selectRaw('fk_sesion_id, SUM(total_pagat) as total')
    ->groupBy('fk_sesion_id')
    ->get()
    ->groupBy('sesion.pelicula.titulo')
    ->map(fn($group) => $group->sum('total'));
```

---

## Performance Considerations

### N+1 Query Problem — Avoided with `with()`

```php
// ❌ BAD: N+1 queries (1 for movies + 1 per movie)
foreach (Pelicula::all() as $p) {
    echo $p->sesiones()->count(); // ← A query per movie
}

// ✅ GOOD: Only 2 queries (movies + screenings in one)
$ps = Pelicula::with('sesiones')->get();
foreach ($ps as $p) {
    echo $p->sesiones->count(); // ← No query, data already loaded
}
```

### Pagination on Large Lists

```php
// Listing 10,000 movies is slow → use pagination
$peliculas = Pelicula::with('categorias')
    ->paginate(12);  // 12 per page, 834 pages total
```

---

## Conclusion

The **CineFlow** data model:

✅ Normalizes correctly to avoid redundancy  
✅ Maintains referential integrity through FKs  
✅ Has strategic indexes for performance  
✅ Uses ACID transactions for consistency  
✅ Maps naturally onto the Eloquent ORM
