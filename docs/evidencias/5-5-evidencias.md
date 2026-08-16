# Evidence 5.5 (Weeks 12–15) — Cine Lumière

> Environment: Docker Compose (no `make`).
> Laravel exposed at: `http://localhost:8001`

## 5.5.1 Start-of-Phase Evidence (Week 12: Configuration and Migrations)

### Evidence A (Migration Code)
- File: `proyecto_final_m0616/app/laravel/database/migrations/0001_01_01_000000_create_users_table.php`
- Screenshot: showing these fields:
  - `$table->string('apellidos');`
  - `$table->string('telefono')->nullable();`
  - `$table->enum('rol', ['cliente', 'admin', 'taquilla'])->default('cliente');`

### Evidence B (Terminal Run — DONE-type output)
1) Start Docker:
```bash
cd /home/etenic/Documentos/DAW/lumiere
docker compose up -d
```

2) Create a temporary DB SOLELY for evidence purposes (doesn't touch the main DB):
```bash
docker compose exec -T mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS projecte_evidencia_1; GRANT ALL PRIVILEGES ON projecte_evidencia_1.* TO 'user'@'%'; FLUSH PRIVILEGES;"
```

3) Run migrate on that DB (this generates RUNNING/DONE):
```bash
docker compose exec -T web_laravel env DB_CONNECTION=mysql DB_HOST=mysql DB_PORT=3306 DB_DATABASE=projecte_evidencia_1 DB_USERNAME=user DB_PASSWORD=secret php laravel/artisan migrate --force
```

> Screenshot: showing lines like:
> `0001_01_01_000000_create_users_table ... DONE`

### Evidence C (Routes)
- File: `proyecto_final_m0616/app/laravel/routes/web.php`
- Screenshot: showing this line:
  - `Route::resource('usuarios', UserController::class);`

## 5.5.2 CRUD Implementation in Laravel (Weeks 13-15)

### Evidence D (View Structure)
- Folder: `proyecto_final_m0616/app/laravel/resources/views/usuarios/`
- Screenshot of the VS Code explorer showing:
  - `index.blade.php`
  - `create.blade.php`
  - `edit.blade.php`
  - `_form.blade.php` (recommended)

### Evidence E (Blade Form)
- Browser URL: `http://localhost:8001/usuarios/create`
- Screenshot: showing the new fields (apellidos, rol, teléfono) and the form.

### Evidence F (PRG Logic in Controller)
- File: `proyecto_final_m0616/app/laravel/app/Http/Controllers/UserController.php`
- Screenshot of `store()` showing:
  - `$request->validate([...])`
  - `User::create(...)`
  - `return redirect()->route(...)->with('status', ...)`

### Evidence G (Final Result)
1) At `http://localhost:8001/usuarios/create`, create a user.
2) Screenshot at `http://localhost:8001/usuarios` right after submitting:
   - The green flash message should be visible: `Usuario creado correctamente`
   - The user should be visible in the table.
