# 🔌 Routes and Endpoints Documentation (API)

> Complete guide to all HTTP routes, parameters, responses, and usage examples.

---

## Table of Contents

1. [Conventions](#conventions)
2. [Public Routes (No Authentication)](#public-routes-no-authentication)
3. [Purchase Routes (Multi-Step)](#purchase-routes-multi-step)
4. [Authenticated Routes (Client)](#authenticated-routes-client)
5. [Admin Routes](#admin-routes)
6. [REST API Routes](#rest-api-routes)
7. [Authentication and Sessions](#authentication-and-sessions)
8. [cURL Examples](#curl-examples)

---

## Conventions

### HTTP Methods

| Method | Use | Example |
|--------|-----|---------|
| `GET` | Fetch data (safe read) | `GET /peliculas` |
| `POST` | Create/modify data (unsafe) | `POST /comprar/entradas` |
| `PUT/PATCH` | Update resource | `PATCH /profile` |
| `DELETE` | Delete resource | `DELETE /reserva/{id}` |

### HTML vs JSON Response

```
GET /peliculas                  → HTML (returns Blade view)
GET /api/seats/{sesion}         → JSON (REST API)
POST /admin/peliculas           → Redirect or JSON (depends on Accept header)
```

### Parameters

- **Route params**: In the URL → `/peliculas/{id}`
- **Query params**: In the query string → `?genero=action&cine=1`
- **Form data**: In the body → `POST /comprar/entradas`

---

## Public Routes (No Authentication)

### 1. Home Page

```
GET /
```

**Description**: Main page with a dynamic hero and now-showing listings

**Response**: HTML (Blade `home.blade.php`)

**Injected data**:
- `peliculas`: 8 featured movies
- `cines`: List of cinemas
- `destacada`: Movie for the hero section
- `tmdbHero`: External movie if none exists locally

---

### 2. List Movies (With Filters)

```
GET /peliculas
```

**Query Parameters**:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `cine` | integer | Cinema ID | `?cine=1` |
| `genero` | string | Category name | `?genero=acción` |
| `fecha` | date | Filter by date | `?fecha=2026-04-20` |
| `search` | string | Full-text search | `?search=terminator` |
| `page` | integer | Page number | `?page=2` |

**Response**: HTML with movie grid

**Example**:
```
GET /peliculas?genero=acción&cine=1&page=2
```

---

### 3. Movie Detail

```
GET /peliculas/{id}
```

**Parameters**:
- `id` (integer, required): Movie ID

**Response**: HTML with:
- Full information (synopsis, duration, poster)
- Assigned categories
- Available sessions (next 7 days)
- "Buy tickets" button

**Example**:
```
GET /peliculas/42
```

---

### 4. List Cinemas

```
GET /cines
```

**Response**: HTML with a list of cinemas and their information

---

### 5. Cinema Detail

```
GET /cines/{id}
```

**Parameters**:
- `id` (integer): Cinema ID

**Response**: HTML with cinema info and available screens

---

## Purchase Routes (Multi-Step)

### Step 1: Select Tickets

#### GET /comprar (Show form)

```
GET /comprar?sesion_id={id}
```

**Query Parameters**:
- `sesion_id` (integer, required): Session ID

**Logic**:
- Looks up the session and its movie/screen/cinema
- Clears previous locks (from an old session)
- Resets the purchase session

**Response**: HTML with:
- Table of ticket types (Adult, Reduced, Family, Senior)
- Prices updated per type
- +/- controls for quantity
- "NEXT" button (disabled until ≥1 is selected)

**Validations**:
- Session must exist
- Session must not be in the past (fecha_hora >= NOW())

---

#### POST /comprar/entrades (Submit selection)

```
POST /comprar/entrades
Content-Type: application/x-www-form-urlencoded
```

**Parameters** (body):

```
sesion_id=5
entrades[adult]=2
entrades[reduit]=1
entrades[familia]=0
entrades[jubilat]=0
```

**Server logic**:
1. Validate session ID
2. Validate quantities (min 1, max 10 total)
3. Calculate total = Σ(preu_base × factor × cantidad)
4. Store in `session['compra']`:
   ```php
   session(['compra' => [
       'sesion_id' => 5,
       'entrades' => ['adult' => 2, 'reduit' => 1, ...],
       'num_entrades' => 3,
       'total' => 24.50,
       'butaques' => []
   ]]);
   ```
5. Redirect to `/comprar/butaques`

**Response**: 
- 302 Redirect to Step 2
- Or 422 if validation fails

**Possible Errors**:

```
422 Unprocessable Entity
Select at least 1 ticket.
// Or
Maximum 10 tickets per transaction.
```

---

### Step 2: Select Seats

#### GET /comprar/butaques (Show map)

```
GET /comprar/butaques
```

**Requirement**: `session['compra']` from Step 1 must exist

**Logic**:
1. Looks up the session from `session['compra']['sesion_id']`
2. Gets occupied seats (from confirmed `reservas`)
3. Gets locked seats (from active `seat_locks`)
4. Gets the user's own seats (their own locks)
5. Clears expired locks (`SeatLock::clearExpired()`)

**Response**: HTML with:
- State legend (gray = free, green = selected, red = occupied, orange = locked)
- Seat map (HTML table)
- "Selected: 0 / 3 seats" indicator
- "NEXT" button (disabled until the required quantity is reached)

**Validations**:
- `session['compra']` must exist
- Number of selected seats must == `num_entrades`

---

#### POST /seat/lock (Lock seat - AJAX)

```
POST /seat/lock
Content-Type: application/json
X-CSRF-TOKEN: {token}
```

**Body**:
```json
{
    "sesion_id": 5,
    "butaca": "A1"
}
```

**Logic**:
1. Validate CSRF
2. Look up existing `SeatLock` for `{sesion_id, butaca}`
3. If one exists and isn't expired → Reject
4. Create a new `SeatLock`:
   - `expires_at = NOW() + 15 minutes`
   - User ID is the current user's (or null if guest)

**Response** (JSON):

**Success**:
```json
{
    "ok": true,
    "message": "Seat locked",
    "lock_id": 123
}
```

**Failure** (seat already locked):
```json
{
    "ok": false,
    "reason": "locked",
    "message": "Seat A1 is already locked by another user"
}
```

**Failure** (seat sold):
```json
{
    "ok": false,
    "reason": "taken",
    "message": "Seat A1 has already been sold"
}
```

---

#### GET /api/seats/{sesion} (Poll status - AJAX)

```
GET /api/seats/5
```

**Parameters**:
- `sesion` (integer): Session ID

**Logic**:
- Retrieves occupied seats (confirmed reservations)
- Retrieves locked seats (non-expired seat_locks)
- Returns JSON

**Response** (JSON):
```json
{
    "sesion_id": 5,
    "taken": ["A1", "A2", "C5"],
    "locked": ["B3", "B4"],
    "available": 172,
    "total": 180,
    "timestamp": "2026-04-18T10:30:45Z"
}
```

**Usage in JavaScript**:
```javascript
// Poll every 10 seconds
setInterval(async () => {
    const res = await fetch('/api/seats/5');
    const data = await res.json();
    updateSeatMap(data);
}, 10000);
```

---

#### POST /comprar/butaques (Confirm selection)

```
POST /comprar/butaques
Content-Type: application/x-www-form-urlencoded
```

**Body**:
```
butaques=A1,A2,B3
```

**Logic**:
1. Validate quantity == `num_entrades`
2. Validate the seats are still available (final check)
3. Store in `session['compra']['butaques'] = ['A1', 'A2', 'B3']`
4. Redirect to Step 3

**Response**: 302 Redirect to `/comprar/pagament`

---

### Step 3: Payment

#### GET /comprar/pagament (Show form)

```
GET /comprar/pagament
```

**Requirement**: `session['compra']` with seats completed

**Response**: HTML with:
- Purchase summary (movie, tickets, seats, total)
- Form: personal details (name, last name, email)
- Payment method selector (Bizum, Card)
- 3D card preview (updates as you type)

---

#### POST /comprar/pagament (Process payment)

```
POST /comprar/pagament
Content-Type: application/x-www-form-urlencoded
```

**Body**:
```
nom=Juan
cognoms=García
email=juan@example.com
metode=targeta
num_targeta=4111111111111111
titular_targeta=JUAN GARCIA
caducitat_mes=12
caducitat_any=27
cvv=123
```

**Logic**:
1. Validate all fields
2. **Simulate payment processing** (no real integration)
3. Create `Reserva` in the DB:
   - Status = 'confirmada'
   - Store seats as CSV: "A1, A2, B3"
   - Store total
   - Generate `ticket_token` (JWT)
4. Release `SeatLock` for the seats
5. Create a `ReservaSeat` for each seat
6. Send confirmation email
7. Redirect to confirmation

**Validations**:
- Validate card structure (simplified)
- Validate CVV (3-4 digits)
- Validate email format
- Validate ticket quantity

**Response**: 302 Redirect to `/comprar/confirmacio`

**Errors**:
```
422 Unprocessable Entity
validation.num_targeta: The card number is not valid.
// Or
validation.email: The email must be valid.
```

---

#### GET /comprar/confirmacio (Final summary)

```
GET /comprar/confirmacio
```

**Response**: HTML with:
- ✅ Purchase completed
- Reservation code
- Details: movie, time, seats, total paid
- QR with `ticket_token` to validate the ticket
- "Download PDF" button (optional)
- "Back to listings" button

---

## Authenticated Routes (Client)

Require `auth:web` middleware

### GET /perfil

```
GET /perfil
```

**Data shown**:
- Name, last name, email, phone
- Option to edit details
- Saved card (last 4 digits, if any)

---

### GET /mis-entradas

```
GET /mis-entradas
```

**Response**: HTML listing confirmed reservations:

| Movie | Cinema | Date | Seats | Price | Action |
|----------|------|-------|---------|--------|--------|
| Terminator | BCN 1 | 2026-04-20 20:00 | A1, A2 | 25.50€ | Download QR |

---

### GET /entrada/qr/{token}

```
GET /entrada/qr/eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Parameter**:
- `token` (string): JWT with ticket data

**Response**: HTML/PDF with:
- QR code
- Movie, time, seats
- Status: ✅ VALID or ❌ ALREADY USED

---

## Admin Routes

Require `role:admin` middleware

### Movies

```
GET /admin/peliculas              # List
GET /admin/peliculas/crear        # Create form
POST /admin/peliculas             # Save new
GET /admin/peliculas/{id}/editar  # Edit form
PATCH /admin/peliculas/{id}       # Save changes
DELETE /admin/peliculas/{id}      # Delete
```

### Screens

```
GET /admin/salas
POST /admin/salas
PATCH /admin/salas/{id}
DELETE /admin/salas/{id}
```

### Sessions

```
GET /admin/sesiones
POST /admin/sesiones
PATCH /admin/sesiones/{id}
DELETE /admin/sesiones/{id}
```

### Users

```
GET /admin/usuarios
POST /admin/usuarios
PATCH /admin/usuarios/{id}
DELETE /admin/usuarios/{id}
```

---

## REST API Routes

Return JSON (for the frontend via Fetch/Axios)

### GET /api/seats/{sesion_id}

See description in [Step 2](#get-apiseatssesion-poll-status--ajax)

### POST /seat/lock

See description in [Step 2](#post-seatlock-lock-seat---ajax)

### POST /seat/unlock

```
POST /seat/unlock
Content-Type: application/json
```

**Body**:
```json
{
    "sesion_id": 5,
    "butaca": "A1"
}
```

**Response**:
```json
{
    "ok": true,
    "message": "Seat unlocked"
}
```

---

## Authentication and Sessions

### Login

```
GET /login                # Show form
POST /login               # Process login
```

**Body (POST)**:
```
email=admin@cineflow.test
password=password
remember=on              # Optional: "remember me"
```

**Validations**:
- Email must exist
- Password must be correct
- If email_verified_at is null (unverified) → Error

**Response**: 
- Success: 302 Redirect to dashboard
- Error: 422 with message

### Register

```
GET /register             # Show form
POST /register            # Create account
```

**Fields**:
- name, email, password (x2)

**Validations**:
- Unique email
- Password >= 8 characters
- Password confirmation

### Logout

```
POST /logout
```

**Response**: 302 Redirect to home

### Verify Email

```
GET /email/verify/{hash}
```

Received via a link in an email. Sets `email_verified_at`.

---

## cURL Examples

### 1. Search movies

```bash
curl "http://localhost:8001/peliculas?genero=acción&cine=1" \
  -H "Accept: text/html"
```

### 2. Select tickets (Step 1)

```bash
curl -X POST "http://localhost:8001/comprar/entrades" \
  -d "sesion_id=5&entrades[adult]=2&entrades[reduit]=1" \
  -b "LARAVEL_SESSION=..." \
  -L
```

### 3. Lock a seat (AJAX)

```bash
curl -X POST "http://localhost:8001/seat/lock" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(curl -s http://localhost:8001 | grep csrf_token | grep -o 'value="[^"]*' | cut -d'"' -f2)" \
  -d '{"sesion_id": 5, "butaca": "A1"}' \
  -b "LARAVEL_SESSION=..." \
  -c cookies.txt
```

### 4. Get seat status (AJAX)

```bash
curl "http://localhost:8001/api/seats/5" \
  -H "Accept: application/json" \
  -b "LARAVEL_SESSION=..." | jq .
```

### 5. Get ticket PDF

```bash
curl "http://localhost:8001/entrada/qr/eyJ..." \
  -o entrada.pdf \
  -H "Accept: application/pdf"
```

---

## HTTP Status Codes

| Code | Meaning | When |
|--------|-------------|--------|
| 200 | OK | Request succeeded |
| 302 | Redirect | Redirect after processing |
| 401 | Unauthorized | Missing authentication |
| 403 | Forbidden | Authenticated but lacks permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Server error (check logs) |

---

## Important Headers

```
X-CSRF-TOKEN: {token}           # Required for POST/PUT/DELETE
X-Requested-With: XMLHttpRequest # For AJAX requests
Accept: application/json         # For JSON responses
Accept: text/html                # For HTML responses
Content-Type: application/json   # If the body is JSON
```

---

## Conclusion

The routes follow RESTful patterns where it makes sense, but prioritize:
- **Clarity**: Descriptive, predictable names
- **Security**: CSRF, authentication, validation
- **UX**: Redirect after processing (POST-redirect-GET)
- **Maintainability**: Thin controllers, services carrying the logic
</content>
