# VoiceBot Production Deployment

This app is split into a Laravel API in `backend/` and a React/Vite client in `frontend/`.

## Backend

1. Install PHP dependencies:

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

2. Configure `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
OPENAI_API_KEY=your_openai_key
OPENAI_BASE_URL=https://api.openai.com/v1
SANCTUM_STATEFUL_DOMAINS=app.example.com
SESSION_DOMAIN=.example.com
FRONTEND_URL=https://app.example.com
```

3. Prepare Laravel:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

4. Point the web server document root to `backend/public`.

5. Ensure `backend/storage` and `backend/bootstrap/cache` are writable by the PHP process.

## Frontend

1. Configure `frontend/.env`:

```env
VITE_API_URL=https://api.example.com/api
```

2. Build static assets:

```bash
cd frontend
npm ci
npm run build
```

3. Deploy `frontend/dist` to the frontend host.

4. Configure the frontend host to route all paths to `index.html`.

## Security Checklist

- Use HTTPS for both frontend and backend.
- Keep `APP_DEBUG=false` in production.
- Store `OPENAI_API_KEY` only on the backend.
- Restrict admin access with the existing `users.is_admin` flag.
- Configure CORS to allow only the production frontend origin.
- Keep database backups and log rotation enabled.

## Verification

Run before release:

```bash
cd backend
php artisan test
```

```bash
cd frontend
npm run build
```

Then verify login, microphone permission, transcription, chat response, audio playback, conversation history, language selection, and admin dashboard access with an admin user.
