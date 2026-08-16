# Deployment guide — CineFlow Booking Engine

## What gets deployed

CineFlow is a **Laravel monolith**: the same service serves the backend (API/logic)
and the frontend (Blade views + assets compiled with Vite). There's no need to
deploy front and back separately. The database can be:

- **SQLite** (default): zero configuration, creates itself. Ideal for the demo,
  but on free tiers with ephemeral disk it gets regenerated on every redeploy.
- **MySQL** (recommended for real persistence): a separate DB service that you
  point to with environment variables.

Everything (back + front + SQLite DB) travels inside a single Docker image, so
"deploying everything" means deploying **a single service**.

---

## Option A — Render (recommended, free)

1. Generate an `APP_KEY` (save it for step 4):

   ```bash
   docker compose exec app php artisan key:generate --show
   ```

   Copy the full value (starts with `base64:...`).

2. Go to <https://dashboard.render.com> → **New +** → **Blueprint**.
3. Connect your GitHub and pick the **AyChEs/cineflow-booking-engine** repo.
   Render auto-detects `render.yaml`.
4. Fill in the variables marked `sync: false`:

   | Variable | Value |
   |---|---|
   | `APP_KEY` | the `base64:...` from step 1 |
   | `APP_URL` | the URL Render assigns you (e.g. `https://cineflow.onrender.com`) |
   | `STRIPE_KEY` / `STRIPE_SECRET` | Stripe **test** keys (or empty for simulated payment) |
   | `MAIL_MAILER` | `smtp` to send tickets by email (or `log` to send nothing) |
   | `MAIL_HOST` `MAIL_PORT` `MAIL_USERNAME` `MAIL_PASSWORD` | your SMTP details (see below) |
   | `MAIL_FROM_ADDRESS` | sender address verified with your SMTP |

5. **Create** and wait for the build. Your demo will be live at the `APP_URL`.

> Render's free tier uses ephemeral disk: the SQLite database gets regenerated
> with demo data on every redeploy. Perfect for a portfolio. For persistent
> data, use MySQL (see below).

---

## Option B — Railway (better if you want a persistent DB)

1. <https://railway.app> → **New Project** → **Deploy from GitHub repo** → pick the
   repo. Railway uses `railway.json` + `Dockerfile`.
2. Add a **MySQL** plugin from within the project (it gives you host/user/password).
3. In **Variables** set: `APP_KEY`, `APP_URL`, `APP_ENV=production`, the mail
   ones, and the DB ones (see the MySQL block below).
4. Deploy.

## Option C — Fly.io

```bash
fly launch --copy-config --no-deploy   # uses fly.toml
fly secrets set APP_KEY="base64:..." APP_ENV=production DB_CONNECTION=sqlite
fly deploy
```

---

## Mail (sending tickets with QR code)

The app emails the ticket with its QR code once the purchase is confirmed. You need
an SMTP provider. Free ones that work in production:

- **Brevo** — <https://app.brevo.com> · 300 emails/day free. In *SMTP & API*
  copy the login (`something@smtp-brevo.com`) and a *master password*:

  ```env
  MAIL_MAILER=smtp
  MAIL_HOST=smtp-relay.brevo.com
  MAIL_PORT=587
  MAIL_USERNAME=xxxxxx@smtp-brevo.com
  MAIL_PASSWORD=your-master-password
  MAIL_FROM_ADDRESS=tickets@yourdomain.com   # sender verified in Brevo
  ```

- **Gmail** — create an *app password* at
  <https://myaccount.google.com/apppasswords>:

  ```env
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=youremail@gmail.com
  MAIL_PASSWORD=the-app-password
  MAIL_FROM_ADDRESS=youremail@gmail.com
  ```

With `MAIL_MAILER=log`, nothing gets sent: the email is written to
`storage/logs/laravel.log` (useful for testing without SMTP).

---

## Production with MySQL (real persistence)

On any platform, instead of SQLite:

```env
DB_CONNECTION=mysql
DB_HOST=<host>
DB_PORT=3306
DB_DATABASE=<db>
DB_USERNAME=<user>
DB_PASSWORD=<pass>
```

`docker/entrypoint.sh` runs migrations and seeders automatically on first
boot.

---

## After deploying

1. Copy your demo's public URL.
2. In your portfolio (`index.html`), replace the `href` of the **Demo** button on
   the *CineFlow Booking Engine* card with that URL.
</content>
