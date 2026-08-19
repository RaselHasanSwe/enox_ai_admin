# EnoX Admin (Laravel)

UI-only admin dashboard for the EnoX AI chatbot. All business logic lives in the FastAPI backend.

## Setup

1. Copy `.env.example` to `.env` and configure:

```env
ENOX_API_URL=http://127.0.0.1:9000/api/v1
ENOX_WS_URL=ws://127.0.0.1:9000
```

2. Install dependencies (already done if created via Composer):

```bash
composer install
php artisan key:generate
```

3. Run the admin app:

```bash
php artisan serve --port=8080
```

4. Log in with the default FastAPI admin user (from `backend/.env`):

```
ADMIN_DEFAULT_EMAIL=admin@enorsia.com
ADMIN_DEFAULT_PASSWORD=changeme123
```

## Pages

| Route | Description |
|-------|-------------|
| `/` | Dashboard KPIs |
| `/conversations` | Searchable conversation inbox |
| `/conversations/{id}` | Full transcript |
| `/handoff` | Live agent queue |
| `/handoff/{id}/chat` | Agent chat panel |
| `/users` | Chat customers |
| `/analytics` | CSAT and tool usage |

## Architecture

Laravel stores only the JWT session token. Every action calls the FastAPI `/api/v1/admin/*` endpoints.
