# ScanUp Deployment Security Review

This document is a deployment-focused security checklist for the ScanUp QR-ID system. It avoids exposing full internal architecture and focuses on practical actions that reduce risk in production.

## Immediate Production Fixes

### 1. Disable Debug Mode

Production must not expose Laravel debug pages.

Recommended live `.env` values:

```env
APP_ENV=production
APP_DEBUG=false
```

Why this matters:

- Debug pages can reveal file paths, SQL details, environment values, stack traces, and framework internals.
- Attackers can use those details to plan targeted attacks.

After changing:

```bash
php artisan optimize:clear
php artisan config:cache
```

### 2. Serve Only the `public` Folder

Apache should point the website root to Laravel's `public` directory only.

Do not expose the project root to the web. The project root contains files such as `.env`, source code, Composer files, SQL scripts, and private documentation.

Safe public entry:

```text
/public/index.php
```

Risky public entry:

```text
/SCANNER-PROD/
```

### 3. Remove `public/hot` in Production

If `public/hot` exists on the live server, remove it. That file is for Vite development mode and can make the app try to load assets from a development server.

### 4. Protect Private Documentation

Private implementation notes, database mapping, and system architecture should not be public.

Private docs should stay in:

```text
bluprints/
```

The `bluprints/` folder should remain ignored by Git and blocked from web access.

### 5. Do Not Deploy Database Dumps

SQL dumps and one-time database scripts should not live inside the public deployment folder.

They may contain:

- tbl names
- schema details
- user data
- operational history
- database names
- migration logic

Move them outside the webroot and do not commit them to a public repository.

### 6. Restrict Crawling

Update production `robots.txt` if the system should not be indexed:

```txt
User-agent: *
Disallow: /
```

Also add HTTP headers to discourage indexing:

```apache
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow, nosnippet, noarchive"
</IfModule>
```

Important: robots rules are not real security. They only guide search engines.

## Recommended Apache Headers

Add these in the production Apache config or `.htaccess` where supported:

```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "same-origin"
    Header always set Permissions-Policy "camera=(self), microphone=(), geolocation=()"
</IfModule>
```

Consider adding a Content Security Policy after testing the landing page, because the app uses external media/widgets.

Starter CSP for testing:

```apache
Header always set Content-Security-Policy "default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; frame-src https://www.youtube.com https://web.facebook.com https://www.facebook.com; connect-src 'self';"
```

Test carefully before enabling strict CSP in production.

## Rate Limiting

Add or confirm throttling for public and login-related endpoints:

- school lookup
- login
- scanner school check
- QR scan submission
- password reset
- setup/register routes

Recommended intent:

- Login routes: strict limit.
- School lookup routes: moderate limit.
- Scanner scan route: moderate but not too low, because scanning may happen repeatedly.

## Database Security

Use a limited MySQL user for production.

Avoid using:

```text
root
```

Recommended permissions for the ScanUp app user:

- Read/write only on ScanUp-owned `tbl_scanup_*` tbls.
- Read-only on EHRIS-owned tbls required for lookup/sync.
- No `DROP`.
- No global privileges.
- No access to unrelated databases.

Protect phpMyAdmin:

- Strong password.
- IP restriction if possible.
- Do not expose phpMyAdmin publicly without access control.

## XSS and JavaScript Review

No obvious dangerous JavaScript patterns were found during the scan:

- No direct `eval`.
- No direct `new Function`.
- No direct `document.write`.
- No obvious user-controlled `v-html`.
- The `innerHTML` usage found is only clearing a container, not injecting user input.

Still recommended:

- Escape all user-visible data by default.
- Avoid adding `v-html` unless absolutely necessary.
- Keep `rel="noopener noreferrer"` on external links opened in new tabs.
- Test CSP before enforcing it.

## SQL Injection Review

Laravel models, query builder, and validation rules are generally safer than raw string SQL.

Keep these rules:

- Use Eloquent or query builder bindings.
- Avoid building SQL strings using request input.
- Validate route params like school IDs, student IDs, and scan payloads.
- Keep scanner school isolation enforced on the backend, not only the frontend.

## File Upload Security

For uploaded photos, signatures, and images:

- Validate MIME type.
- Validate file extension.
- Limit file size.
- Store uploads outside executable PHP paths.
- Never allow uploaded `.php`, `.phtml`, `.phar`, `.js`, or `.html`.
- Generate server-side filenames instead of trusting uploaded names.

## Unused or Risky File Scan

No files were deleted. This section marks files by recommended action.

### High Risk: Move Outside Project or Remove From Deployment

These appear to be database dumps, legacy SQL, or one-time scripts. They should not be deployed publicly.

```text
scan_up (11).sql
database/scan_up (6).sql
database/scan_up_rebuild.sql
add_notification_preference.sql
database/add_designation_and_profile_photo_to_users.sql
database/add_signature_path_to_users.sql
```

Reason:

- They expose database structure and old database names.
- Some target old `scan_up` storage.
- One script can rebuild/drop old database state and is not safe for production deployment.

### Keep Private: One-Time Admin Scripts

These are useful for controlled maintenance, but should not be public.

```text
database/scripts/create_scanup_tbls_phpmyadmin_safe.sql
database/scripts/migrate_scanup_to_ehris_tbl_scanup.sql
```

Recommended:

- Keep in a private admin folder or offline backup.
- Do not expose through Apache.
- Do not commit to a public repository.

### Likely Unused or Accidental

These do not appear connected to the active Laravel/Vue app and should be reviewed before removal.

```text
Microsoft/
LOCATOR_TEMP/
SCANNER-PROD/
check_stats_data.php
user_roles_clean.php
temp_attendance_schema.txt
scripts/dev-runner.php
```

Notes:

- `Microsoft/` looks like an accidental PowerShell/cache folder.
- `LOCATOR_TEMP/` contains design source files, not runtime assets.
- Nested `SCANNER-PROD/` looks like a duplicate project fragment.
- Standalone PHP helper files should not sit in the webroot unless intentionally protected.

### Development-Only

These may be useful locally but are not needed on production servers.

```text
tools/generate_favicons.php
tools/make_favicon_ico.php
tests/
.phpunit.result.cache
```

Recommended:

- Keep in source control if useful for development.
- Do not deploy to production unless needed.

### Keep: Currently Referenced by the System

Do not remove these unless the related feature is retired.

```text
ID/1.png
ID/2.png
theme/step-up.png
tcpdf-main/tcpdf.php
public/image_temp/
public/images/
public/school/
START_SCANUP_UI_EXE_FIXED.bat
START_SCANUP_UI_EXE_FIXED.ps1
```

Reason:

- ID template files are used by ID generation logic.
- TCPDF is used by PDF/ID generation logic.
- Public images are used by the landing page and UI.
- The BAT/PS1 launcher is used for scanner startup.

### Needs Confirmation

```text
image_temp/
school/
START_SCANUP_UI_EXE_WRAPPER.bat
```

Reason:

- Similar assets also exist under `public/`.
- Some root-level assets may be legacy or fallback assets.
- The wrapper BAT may be old if the fixed BAT is now the official launcher.

## Recommended `.gitignore` Additions

Use these if they are not already present:

```gitignore
/bluprints/
*.sql
/database/*.sql
/database/backups/
*.bak
*.backup
*.zip
*.7z
*.rar
.phpunit.result.cache
public/hot
```

Be careful with `*.sql`: if you intentionally keep safe schema scripts in source control, move private dumps elsewhere first.

## Safe Cleanup Order

1. Back up the project folder.
2. Move SQL dumps and old SQL scripts outside the webroot.
3. Confirm the app still runs.
4. Remove accidental folders only after confirming no references.
5. Keep private docs in `bluprints/` and ignored by Git.
6. Set production `.env` values.
7. Clear and cache Laravel config.
8. Build frontend assets.
9. Test login, EHRIS fetch, student management, scanner, attendance, and ID generation.

## Final Security Priority

Highest priority:

1. `APP_DEBUG=false`
2. Serve only `/public`
3. Remove database dumps from deployment
4. Protect `bluprints/`
5. Add rate limiting
6. Use a limited database user
7. Protect phpMyAdmin
8. Add noindex and security headers

