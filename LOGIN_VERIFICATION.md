# Scan-Up Login Verification

## Backend login flow (no hardcoded credentials)

- **Endpoint:** `POST /api/login` (JSON: `email`, `password`)
- **Auth:** `AuthController@login` finds user by email, verifies with `Hash::check()`, issues Sanctum token
- **Guards:** `config/auth.php` → `providers.users` = Eloquent `App\Models\User`; API uses `auth:sanctum`
- **DB:** Uses `.env` (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
- **Passwords:** Stored bcrypt; verified with Laravel `Hash::check()`

## Reset admin password (from env)

```bash
# Option 1: From .env (set ADMIN_EMAIL and ADMIN_PASSWORD in .env)
php artisan scanup:reset-admin-password

# Option 2: Inline (no .env)
php artisan scanup:reset-admin-password --email=admin@example.com --password=YourSecurePassword
```

No passwords or emails are hardcoded in code.

## Seed users from env only

In `.env` set (optional):

- `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`
- `SEED_TEACHER_EMAIL`, `SEED_TEACHER_PASSWORD`
- `SEED_GUARD_EMAIL`, `SEED_GUARD_PASSWORD`

Then:

```bash
php artisan db:seed
```

Only roles are always seeded; users are created/updated only when the matching env vars are set.

## Vue frontend login

1. **API base URL:** Set `VITE_APP_URL=http://127.0.0.1:8000` in `.env` so `npm run dev` (port 5173) calls Laravel.
2. **Login page:** `resources/js/views/Login.vue` → `POST /api/login` with `email`, `password`.
3. **On success:** Stores token, sets `Authorization: Bearer <token>`, redirects by role (Admin → `/admin`, Teacher → `/teacher`, Guard → `/guard`).
4. **On error:** Shows `err.response?.data?.errors?.email?.[0]` or `err.response?.data?.message`.

## Guard scanner (no login)

- **URL:** `http://127.0.0.1:8000/guard`
- **Route:** `routes/web.php` → `GET /guard` → `view('guard')` (no auth)
- **API:** `POST /api/attendance/scan`, `GET /api/attendance/public/recent` are public (no Sanctum)

## Verification checklist

1. **DB:** `php artisan migrate`; ensure `users` and `personal_access_tokens` (with `expires_at`) exist.
2. **Password:** Run `php artisan scanup:reset-admin-password` with env or `--email`/`--password`.
3. **Backend:** `php artisan serve` → `http://127.0.0.1:8000`.
4. **Frontend:** `npm run dev` → open app from `http://127.0.0.1:8000` (or 5173 with correct `VITE_APP_URL`).
5. **Login:** Use the email/password you set (env or reset command).
6. **Guard:** Open `http://127.0.0.1:8000/guard` in a new tab → scanner works without login.
