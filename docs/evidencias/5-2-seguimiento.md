# 5.2 Follow-up Report I (Week 12)

## Context and objective
This phase closed out **Validation I (M0613)** with a CRUD built in **PHP** using a **manual MVC structure** and the **PRG (Post/Redirect/Get)** pattern, and started migrating that same CRUD to **Laravel (M0616)** for "Exercise 3: CRUD Migration to Laravel".

The main objectives were:
- Consolidate a correct MVC flow (front controller → controller → model → view).
- Ensure basic good practices: **PDO with prepared statements**, form validation, output escaping, and PRG.
- Prepare the start of Laravel with **migrations**, **resource routes**, a **resource controller** and **Blade views**, and have evidence ready for the document.

## Tasks completed
### PHP (manual MVC)
- Front controller operational in the manual MVC, delegating the action to the controller.
- Implementation of the user list from the controller and model.
- Security and robustness review:
  - Queries via PDO (prepare/execute) with error handling via try/catch.
  - HTML escaping in views with `htmlspecialchars`.
  - Validation and sanitization of `id` (GET/POST) so only numeric values are accepted.
  - PRG after POST operations (add/edit/delete).

### Laravel (CRUD migration)
- Customized the `users` migration with new fields: `apellidos`, `telefono` (nullable) and `rol` (enum).
- Updated the `User` model (mass assignment) and kept the password hashing via the `password => hashed` cast.
- Built a full CRUD with `Route::resource('usuarios', ...)` routes and the `UserController` controller:
  - `store()` with `validate()` + `User::create()` + `redirect()->with('status', ...)`.
  - `update()` with validation and conditional password update.
  - `destroy()` with deletion and a flash message.
- Created Blade views under `resources/views/usuarios` and a base layout with a green flash message.
- Resolved database mismatches:
  - Added an extra migration to add the new fields to an already-existing `users` table.

## Incidents found and solutions applied
- **PHP version on the host**: running `php artisan` failed due to version requirements. Solution: run commands inside the Docker environment.
- **Permissions on `storage/` and `bootstrap/cache/`**: errors writing logs and compiling views. Solution: adjusted permissions and cleared caches.
- **Out-of-sync MySQL schema** ("Unknown column apellidos"): the `users` table already existed without the custom fields. Solution: additional migration to add the columns.

## Current status
- Manual PHP MVC working, with listing, create, edit and delete (PRG) and basic security measures in place.
- Laravel with a working "usuarios" CRUD, resource routes, Blade views and migrations consistent with the database.

## Next steps (Week 13)
- Capture the final browser evidence (the `/usuarios/create` form and the listing with the flash message after creating a user).
- Review the presentation (minimal styling) and text consistency (CA/ES), unifying if needed.
- Prepare the final submission (repo cleanup and `.gitignore` review).
