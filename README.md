# Laravel Reverb Server

A self-hosted WebSocket server management dashboard built on top of [Laravel Reverb](https://reverb.laravel.com). Manage multiple Reverb applications, monitor live connections and channels, and debug events in real time — all from a single web interface.

## Features

- **Multi-app management** — create and manage multiple Reverb applications with individual credentials
- **Live dashboard** — monitor active connections and channels with 3-second polling
- **Connection history** — sparkline charts showing connection trends over the last 60 minutes
- **Debug console** — subscribe to channels and inspect incoming events in real time
- **Event sender** — trigger events to any channel directly from the UI
- **Docker-ready** — pre-built images with SQLite, MariaDB, and PostgreSQL support

## Requirements

- Docker & Docker Compose
- A GitHub Container Registry account (for pulling the image)

## Quick Start

### 1. Pull the compose file

```bash
# SQLite (simplest, no external database)
curl -o docker-compose.yml https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/docker-compose.sqlite.yml
curl -o .env https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/.env.sqlite.example
```

### 2. Configure environment

```bash
cp .env.sqlite.example .env
```

Edit `.env` and fill in the required values:

```env
APP_KEY=          # generate with: openssl rand -base64 32
APP_URL=https://your-domain.com

REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

### 3. Start the server

```bash
docker compose up -d
```

### 4. Seed the first admin user

```bash
docker compose exec app php artisan db:seed
```

Default credentials:
- **Email:** `admin@example.com`
- **Password:** `password`

> Change the password immediately after first login via **Settings → Security**.

---

## Database Options

### SQLite (default)

Best for single-server deployments. No external database required.

```bash
curl -o docker-compose.yml https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/docker-compose.sqlite.yml
curl -o .env https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/.env.sqlite.example
# Edit .env, then:
docker compose up -d
```

### MariaDB 10.11

```bash
curl -o docker-compose.yml https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/docker-compose.mariadb.yml
curl -o .env https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/.env.mariadb.example
# Edit .env, then:
docker compose up -d
```

### PostgreSQL 16

```bash
curl -o docker-compose.yml https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/docker-compose.postgres.yml
curl -o .env https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/.env.postgres.example
# Edit .env, then:
docker compose up -d
```

---

## Environment Variables

| Variable | Required | Description |
|---|---|---|
| `APP_KEY` | ✓ | Application encryption key |
| `APP_URL` | ✓ | Full URL of the dashboard (e.g. `https://reverb.example.com`) |
| `REVERB_HOST` | ✓ | Public hostname clients use to connect via WebSocket |
| `REVERB_PORT` | ✓ | Public port (usually `443` with HTTPS, `8080` without) |
| `REVERB_SCHEME` | ✓ | `https` for production, `http` for local |
| `DB_CONNECTION` | ✓ | `sqlite`, `mysql`, or `pgsql` |
| `DB_HOST` | MySQL/PgSQL | Database host (use `db` when using Docker Compose) |
| `DB_DATABASE` | MySQL/PgSQL | Database name |
| `DB_USERNAME` | MySQL/PgSQL | Database user |
| `DB_PASSWORD` | MySQL/PgSQL | Database password |
| `APP_IMAGE_TAG` | — | Image version tag (default: `latest`) |

---

## Reverse Proxy

### Nginx — same domain, path-based

```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;

    # Dashboard
    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # WebSocket
    location /app/ {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 3600s;
    }
}
```

### Nginx — separate subdomain for WebSocket

```nginx
# Dashboard
server {
    listen 443 ssl;
    server_name reverb-admin.example.com;
    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# WebSocket
server {
    listen 443 ssl;
    server_name ws.example.com;
    location / {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_read_timeout 3600s;
    }
}
```

---

## Connecting Clients

Clients connect to this Reverb server using the **Pusher JS** client or **Laravel Echo**:

```js
// Pusher JS
import Pusher from 'pusher-js';

const pusher = new Pusher('YOUR_APP_KEY', {
    wsHost: 'ws.example.com',
    wsPort: 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    cluster: '',
});
```

```js
// Laravel Echo
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'YOUR_APP_KEY',
    wsHost: 'ws.example.com',
    wsPort: 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
});
```

App credentials (key, secret, app ID) are available in the **Apps** section of the dashboard.

---

## Updating

```bash
# Pull the latest image
docker compose pull

# Restart with the new image
docker compose up -d
```

To pin a specific version:

```env
APP_IMAGE_TAG=1.2.3
```

---

## Development

```bash
git clone https://github.com/adityadarma/laravel-reverb-server.git
cd laravel-reverb-server

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run dev

# In a separate terminal
php artisan reverb:start
php artisan serve
```

---

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
