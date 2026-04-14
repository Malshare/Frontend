# MalShare Frontend

PHP web application powering malshare.com — a community-driven public malware sample repository.

## Tech Stack

- PHP 8.4 on Apache
- MySQL 8
- Wasabi S3-compatible object storage (malware sample binaries)
- AWS SDK for PHP (`aws/aws-sdk-php`)
- Bootstrap CSS, jQuery, D3.js
- Google reCAPTCHA v2
- VirusTotal context widget
- Mailgun (SMTP via PEAR Mail/Net_SMTP)

## Project Structure

```
html/                          # Web root (Apache document root is /var/www/html)
  server_includes.php          # Core framework: ServerObject class, DB, S3, queries, registration (~2150 lines)
  api.php                      # REST API endpoints
  account.php                  # Logged-in user account page (quota/usage)
  admin.php                    # Admin-only API analytics dashboard (D3.js charts, top users)
  sample.php                   # Sample detail view
  search.php                   # Search page
  upload.php                   # File upload (max 26MB)
  index.php                    # Homepage with recent samples
  include/i18n.php             # Translation system using t() and h() helpers
  include/stats.php             # Stats class (not yet wired up — prep for stats page)
  include/disposable_email_domains.php  # Throwaway-email blocklist for register_user()
  i18n/en.php                  # English strings (canonical)
  i18n/de.php                  # German translations
  i18n/fr.php                  # French translations
  css/, js/, images/           # Static assets
docker/
  docker-compose.yaml          # Local dev: MySQL + Nginx proxy + PHP-Apache
  conf.d/default.conf          # Nginx reverse proxy config for dev
Dockerfile                     # Production image
composer.json                  # PHP dependencies
malshare_db.sql                # Database schema + stored procedures
.github/workflows/docker.yml   # CI: build image, push to GHCR, trigger conf deployment
```

## Key Conventions

### i18n

- All user-facing strings should use `h('section.key')` (HTML-escaped) or `t('section.key')` (raw)
- Strings live in `html/i18n/{en,de,fr}.php` as nested associative arrays
- Keys use dot notation: `h('search.title')` resolves to `['search']['title']`
- `h()` wraps `t()` with `htmlspecialchars()` — use `h()` in HTML output
- Template variables use `{{var}}` syntax: `t('register.success_body', array('email' => $email))`
- Always add keys to all three locale files when adding new strings

### PHP Patterns

- `server_includes.php` contains the `ServerObject` class — the main application object
- Environment config is via `getenv()`, not config files
- Database access is via MySQLi prepared statements
- HTML output is built as string concatenation in PHP methods (not templates)
- `$this->escape_html()` for user data in HTML output
- `$this->secure()` for sanitizing input
- `ServerObject::client_ip()` for the client's real IP address — uses `CF-Connecting-IP` (set by Cloudflare) with `REMOTE_ADDR` fallback. Never use `$_SERVER['REMOTE_ADDR']` directly.
- Error messages with numeric codes use `error_die()` / `error_die_with_code()` which auto-generate pre-filled GitHub issue links via `github_issue_url()` and `error_message_html()`
- Keep SQL queries inside `ServerObject` methods, not in page files. Expose data via methods that return arrays (e.g., `get_user_quota()` returns `['limit' => int, 'remaining' => int]`).

### Authentication

- Web login uses a persistent cookie `mapi_key` (30-day expiry) containing the API key
- `auth.php` handles login (sets cookie) and logout (clears cookie)
- Check login state with `isset($_COOKIE['mapi_key']) && $_COOKIE['mapi_key'] !== ''`
- Validate the key with `new UserObject($share->sql, $api_key, true)` — check `$user->ready`
- No PHP sessions are used; the cookie is the sole auth mechanism
- `UserObject` properties: `id`, `api_key`, `active`, `approved`, `recursiveUrlDownloadAllowed`, `is_admin`, `ready`

### Admin Access

- Admin users have `is_admin = 1` in `tbl_users` (set directly in the database)
- Check admin status via `$user->is_admin` (boolean)
- Admin-only pages must check `!$user->ready || !$user->is_admin` and redirect non-admins to `index.php` silently (no error message revealing the page exists)
- The nav bar shows an "Admin" button (yellow `btn-warning`) only for admin users
- `nav.php` checks for an existing `$user` variable in scope, falling back to instantiating a `UserObject` if only `$share` is available

### API Rate Limiting

- All API endpoints that return data must call `$share->update_query_limit()` before executing
- Exempt endpoints: `getlimit` (quota check), `download_url_check` (status poll), `upload` (incentivizes contributions), `terminate` (self-service)
- `get_user_quota($api_key)` returns `['limit' => int, 'remaining' => int]` for web UI use
- `get_user_limit()` is the JSON API wrapper (sets headers, returns JSON)

### API Call Logging

- Every valid API call is logged to `tbl_api_calls` (user_id, endpoint, unix timestamp) via `$share->log_api_call()` in `api.php`
- Logging is fail-silent — INSERT failures never break the API response
- All endpoints are logged, including rate-limit-exempt ones, for complete volume tracking
- Analytics query methods in `ServerObject`: `get_api_calls_per_day()`, `get_api_calls_per_month()`, `get_api_calls_by_endpoint()`, `get_api_top_users()`, `get_api_calls_total()`
- Production MySQL uses `sql_mode=only_full_group_by` — all non-aggregated SELECT columns must appear in GROUP BY

### Search

- `sample_search()` in `server_includes.php` handles all search types: hash (exact match), `source:` prefix (FULLTEXT), `type:` prefix (file type), and default (source LIKE prefix match)
- The `source:` search uses a FULLTEXT index (`ft_source`) on `tbl_sample_sources.source` with `MATCH...AGAINST` in BOOLEAN MODE, with a LIKE fallback for edge cases
- The FULLTEXT index must exist in the database before `source:` searches work — see `malshare_db.sql` for schema

### Conditional Sections

- Hide sections (tables, headings) when there's no data rather than showing empty tables
- Example: "Observed File Names" section only renders when results exist

## Environment Variables

All read via `getenv()` in `server_includes.php`.

### Database
- `MALSHARE_DB_HOST`, `MALSHARE_DB_USER`, `MALSHARE_DB_PASS`, `MALSHARE_DB_DATABASE`
- `MALSHARE_DB_PORT` (optional), `MALSHARE_DB_CERT` (optional, CA cert path for TLS)

### Sample Storage
- `MALSHARE_SAMPLES_ROOT` — path to sample binaries (default: `/mw/repository/binaries/`)
- `MALSHARE_UPLOAD_SAMPLES_ROOT` — path to uploaded samples (default: `/mw/uploads/`)

### Wasabi S3
- `WASABI_ENDPOINT`, `WASABI_REGION`, `WASABI_KEY`, `WASABI_SECRET`, `WASABI_BUCKET`

### Services
- `MALSHARE_RECAPTCHA_SECRET` — set to `DISABLED` to bypass captcha
- `VT_CONTEXT_KEY` — VirusTotal API key
- `VT_CONTEXT_URL` — VirusTotal widget URL
- `MALSHARE_MAILGUN_SMTP`, `MALSHARE_MAILGUN_PORT`, `MALSHARE_MAILGUN_FROM`, `MALSHARE_MAILGUN_USERNAME`, `MALSHARE_MAILGUN_PASSWORD`

## Local Development

```bash
cd docker
docker-compose up
# Access at http://localhost/
```

Limitations without API keys: no reCAPTCHA, no Mailgun emails, no sample files.

## CI/CD

Push to `main` triggers `.github/workflows/docker.yml`:
1. Build Docker image from `Dockerfile`
2. Push to `ghcr.io/malshare/frontend` (tags: `latest` + `sha-<commit>`)
3. Trigger `upstream-image-built` dispatch on `Malshare/conf` repo
4. Conf repo deploys the new image to the server via SSH

The `CONF_DISPATCH_TOKEN` secret (malshare-bot PAT) authorizes the dispatch.

## Daily Exports

The `/daily/` path serves browsable directory listings of daily hash export files generated by pymalshare's `generate_daily.py`. Apache has `Options +Indexes` enabled for this path via `daily-listing.conf`. The files come from a shared Docker volume (`daily_exports`) mounted read-only at `/var/www/html/daily/`.

## API Endpoints (via `api.php`)

`getlist`, `getlistraw`, `getsources`, `getsourcesraw`, `getfile`, `details`, `hashlookup`, `type`, `search`, `gettypes`, `upload`, `getfilenames`, `getlimit`, `download_url`, `download_url_check`

Standard API keys allow 2000 calls/day.

## Updating Dependencies

```bash
composer update --no-interaction
```

This updates `composer.lock`. The only direct dependency is `aws/aws-sdk-php` (constraint `^3.293`). Transitive deps include Guzzle, Symfony polyfills, and PSR packages. Commit the updated `composer.lock` after verifying no issues.

## Maintenance

Keep this CLAUDE.md up-to-date when adding new features, discovering project patterns, or learning new things about the codebase. Also update `CLAUDE.md` and `README.md` in the `Malshare/conf` repo when changes affect deployment or cross-repo architecture.
