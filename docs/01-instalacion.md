# 📋 Installation and Local Setup Guide

> Complete instructions for setting up and running **CineFlow** in your local development environment.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Project Structure](#project-structure)
3. [Step-by-Step Installation](#step-by-step-installation)
4. [Environment Variable Configuration](#environment-variable-configuration)
5. [Database Initialization](#database-initialization)
6. [Verifying the Installation](#verifying-the-installation)
7. [First Steps in the Application](#first-steps-in-the-application)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting, make sure you have installed on your machine:

| Requirement | Minimum Version | Description |
|-----------|-----------------|-------------|
| **Git** | 2.30 | Version control |
| **Docker** | 20.10 | Containerization |
| **Docker Compose** | 1.29 | Container orchestration |
| **Available RAM** | 4 GB | To run the containers |
| **Disk space** | 2 GB | Docker images + data |

### Verify prerequisites are installed

```bash
# Check Git
git --version
# Expected: git version 2.30.x or higher

# Check Docker
docker --version
# Expected: Docker version 20.10.x or higher

# Check Docker Compose
docker compose version
# Expected: Docker Compose version 2.x or higher
```

---

## Project Structure

```
cineflow/
├── proyecto_final_m0616/
│   ├── app/
│   │   └── laravel/              # Laravel application (main app)
│   │       ├── app/
│   │       │   ├── Http/         # Controllers and middlewares
│   │       │   ├── Models/       # Eloquent models
│   │       │   ├── Services/     # Business logic (TMDB, payments, etc.)
│   │       │   └── Console/      # Artisan commands
│   │       ├── config/           # App configuration
│   │       ├── database/         # Migrations, seeders, factories
│   │       ├── resources/        # Blade views, CSS, JS
│   │       ├── routes/           # Route definitions
│   │       ├── storage/          # Logs and files
│   │       ├── tests/            # Automated tests
│   │       ├── .env.example      # Environment variable template
│   │       └── artisan           # Laravel CLI
│   └── docs/
│       ├── README.md
│       ├── 01-instalacion.md    # This file
│       ├── 02-arquitectura.md   # System architecture
│       ├── 03-base-datos.md     # Data model
│       ├── 04-logica-negocio.md # Business logic
│       └── 05-api-endpoints.md  # Routes and endpoints
├── docker/                       # Docker configuration
│   ├── web/
│   │   ├── Dockerfile
│   │   ├── entrypoint.sh
│   │   └── php-dev.ini
│   └── db/
├── docker-compose.yml            # Service orchestration
└── .env.example                  # Base environment variables

```

---

## Step-by-Step Installation

### 1️⃣ Clone the Repository

```bash
# Clone the full repository
git clone <REPOSITORY_URL> cineflow
cd cineflow

# (If you're reviewing an existing checkout)
# Just open the folder in your editor

# Verify we're in the right directory
ls -la
# You should see: proyecto_final_m0616, docker, docker-compose.yml, etc.
```

### 2️⃣ Prepare Environment Variables

```bash
# Navigate to the Laravel folder
cd proyecto_final_m0616/app/laravel

# Copy the .env.example template to .env
cp .env.example .env

# Edit .env with your editor of choice if needed
nano .env
# Or in VS Code: code .env

# The defaults in .env.example are already set up for Docker
# If everything looks right, no changes are needed
```

#### Important Environment Variables

```env
# Database (pre-configured for Docker)
DB_CONNECTION=mysql
DB_HOST=php-basic-mysql        # Docker container name
DB_PORT=3306
DB_DATABASE=projecte           # DB name
DB_USERNAME=root
DB_PASSWORD=root

# TMDB API (optional - to fetch movie posters)
TMDB_API_KEY=your_key_here     # Get one at https://www.themoviedb.org/settings/api

# Application
APP_NAME="CineFlow"
APP_ENV=local                  # local | production
APP_DEBUG=true                 # true in development | false in production
APP_URL=http://localhost:8001  # Local access URL
APP_KEY=                        # Generated automatically with artisan
```

**To get a TMDB_API_KEY:**
1. Go to https://www.themoviedb.org/settings/api
2. Create a free account if you don't have one
3. Request an API key (The Movie Database Task)
4. Copy the key and paste it into APP_KEY in .env

### 3️⃣ Bring Up the Docker Containers

```bash
# Go back to the project root
cd /path/to/cineflow

# Build and start all the containers
# This will pull images, install dependencies, and build assets
docker compose up -d

# Check container status
docker compose ps

# Expected:
# NAME                COMMAND             STATUS
# php-laravel-web     apache2-foreground  Up
# php-basic-mysql     docker-entrypoint...  Up
# phpmyadmin          docker-php-entrypoint...  Up
```

This step will take **5-15 minutes** the first time because it:
- Downloads the base images
- Installs PHP extensions
- Installs Node.js (to build Tailwind)
- Runs `npm install` and `npm run build`
- Initializes MySQL

### 4️⃣ Generate Keys and Optimize

```bash
# Generate APP_KEY (automatically stored in .env)
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan key:generate"

# Clear config cache
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan optimize:clear"
```

### 5️⃣ Run Migrations and Seeders

```bash
# Run migrations (create DB tables)
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan migrate"

# Run seeders (populate DB with test data)
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan seed"

# Check in phpMyAdmin (http://localhost:8082)
# User: root | Password: root
```

---

## Environment Variable Configuration

### The Provided `.env.example` File

The **`.env.example`** file includes all the necessary variables, pre-configured for Docker:

```env
APP_NAME="CineFlow"
APP_ENV=local
APP_KEY=                                    # ← Generated automatically
APP_DEBUG=true
APP_URL=http://localhost:8001

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=php-basic-mysql                   # ← Docker container name
DB_PORT=3306
DB_DATABASE=projecte
DB_USERNAME=root                          # ← MySQL user (don't change in dev)
DB_PASSWORD=root                          # ← MySQL password (don't change in dev)

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS="testing@example.com"

TMDB_API_KEY=                             # ← Optional: get one at https://www.themoviedb.org

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=127.0.0.1
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
```

### ⚠️ Important Notes

1. **Don't version-control `.env`**: The `.env` file is in `.gitignore` for security reasons
2. **Use `.env.example` as a reference**: For reviewing without exposing credentials
3. **In production, change sensitive values**: Passwords, APP_KEY, etc.

---

## Database Initialization

### Table Structure

The application ships with migrations that automatically create all the necessary tables:

```bash
# Check migration status
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan migrate:status"

# If something goes wrong, roll back all migrations:
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan migrate:reset"

# Then run again:
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan migrate --seed"
```

### Seed Data (Seeders)

The seeders automatically create:

- **3 test users** (admin, box office, customer)
- **2 cinemas** with test movies and screenings
- **5 sample movies**
- **Screen data** with varying capacity

### Test Users

Sign in with these credentials to try out the different roles:

| Email | Password | Role | Features |
|-------|-----------|-----|-----------------|
| `admin@cineflow.test` | `password` | admin | CRUD for movies, screens, screenings, users |
| `taquilla@cineflow.test` | `password` | box office | View bookings, validate tickets |
| `cliente@cineflow.test` | `password` | customer | Buy tickets, view own bookings |

---

## Verifying the Installation

### ✅ Verification Checklist

```bash
# 1. Check that the containers are running
docker compose ps
# Expected: 3 containers UP (web, db, phpmyadmin)

# 2. Check that the app responds
curl http://localhost:8001
# Expected: HTML of the home page

# 3. Check that the DB is reachable
# In browser: http://localhost:8082
# User: root | Password: root
# You should see the "projecte" DB with its tables

# 4. Check application logs
docker compose logs php-laravel-web | tail -50
# There shouldn't be any critical errors

# 5. Check that assets are compiled
docker exec php-laravel-web ls -la /var/www/html/laravel/public/build/
# You should see compiled CSS and JS files
```

### 🌐 Access URLs

| Service | URL | Notes |
|----------|-----|-------|
| Web app | http://localhost:8001 | Public listings and purchase flow |
| phpMyAdmin | http://localhost:8082 | Visual DB manager |
| PHP logs | `docker compose logs php-laravel-web` | View app errors |

---

## First Steps in the Application

### 1. Access the Home Page

```
http://localhost:8001
```

You should see:
- ✅ Hero section with a featured movie
- ✅ Listing of available movies
- ✅ Filters by cinema, genre, showtime

### 2. Browse Movies and Screenings

- Click on a movie to see its details
- Click on a screening to start the purchase flow

### 3. Purchase Process (3 steps)

**Step 1: Select Tickets**
- Choose the quantity of tickets by type (Adult, Reduced, Family, Senior)
- Click "NEXT"

**Step 2: Select Seats**
- Click on available seats (gray)
- Deselect if you change your mind
- Seats are color-coded by status:
  - 🟩 Green: Selected by you
  - 🟥 Red: Already sold
  - 🟨 Orange: Locked by other buyers (expires in 15 min)

**Step 3: Pay**
- Choose payment method (Bizum or Card)
- Fill in personal details
- Confirm payment

### 4. Access the Admin Panel (Optional)

```
http://localhost:8001/admin
Credentials: admin@cineflow.test / password
```

Admin features:
- CRUD for movies (create, edit, delete)
- Manage screens and screenings
- View all bookings
- Manage users

---

## Troubleshooting

### ❌ Containers won't start

```bash
# See detailed logs
docker compose logs

# Try forcing a rebuild
docker compose down
docker compose up -d --build

# If it still doesn't work, clear volumes
docker compose down -v
docker compose up -d
# ⚠️ This will delete the DB. Then run again:
# docker exec php-laravel-web sh -c "cd /var/www/html/laravel && php artisan migrate --seed"
```

### ❌ Error: "Connection refused" on the database

```bash
# The DB takes a few seconds to become ready
# Wait 10 seconds and reload the page

# If it persists:
docker exec php-basic-mysql mysql -uroot -proot -e "SELECT 1"
# If it returns an error, the DB didn't start correctly

# Restart just the DB
docker compose restart php-basic-mysql
```

### ❌ "No page found" or 404 errors

```bash
# Clear cache
docker exec php-laravel-web php artisan cache:clear
docker exec php-laravel-web php artisan route:clear

# Regenerate config
docker exec php-laravel-web php artisan config:cache
```

### ❌ Assets (CSS, JS) not loading

```bash
# Recompile assets
docker exec php-laravel-web sh -c "cd /var/www/html/laravel && npm run build"

# Clear browser history
# In Chrome: Ctrl+Shift+Delete (clear cache)
# Then reload: Ctrl+F5
```

### ❌ File permission errors

```bash
# Fix permissions recursively
docker exec php-laravel-web sh -c "chmod -R 775 /var/www/html/laravel/storage"
docker exec php-laravel-web sh -c "chmod -R 775 /var/www/html/laravel/bootstrap/cache"
```

### ❌ Port 8001 already in use

```bash
# Check which process is using the port
lsof -i :8001

# Or change the port in docker-compose.yml:
# Change "8001:80" to "8002:80"
docker compose down
docker compose up -d
# Then access http://localhost:8002
```

---

## Final Verification

Run this command to check everything is working:

```bash
# Verification script
docker compose ps && \
docker exec php-laravel-web php artisan optimize && \
echo "✅ All checks passed"

# If everything is green, you're ready to go!
```

---

## Next Steps

Once the installation is complete:

1. **Read [02-arquitectura.md](02-arquitectura.md)** to understand the project structure
2. **Check [03-base-datos.md](03-base-datos.md)** to understand the data model
3. **Review [05-api-endpoints.md](05-api-endpoints.md)** to see all available routes
4. **Read [04-logica-negocio.md](04-logica-negocio.md)** to understand the purchase and payment logic

---

**Need help?** Check the [Troubleshooting](#troubleshooting) section or open an issue in the repository.
