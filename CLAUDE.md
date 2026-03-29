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
  server_includes.php          # Core framework: ServerObject class, DB, S3, queries (~2000 lines)
  server_registration.php      # User registration, email via Mailgun
  api.php                      # REST API endpoints
  sample.php                   # Sample detail view
  search.php                   # Search page
  upload.php                   # File upload (max 26MB)
  index.php                    # Homepage with recent samples
  include/i18n.php             # Translation system using t() and h() helpers
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
- Keys use dot notation: `h('sample.yara_hits')` resolves to `['sample']['yara_hits']`
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

### Conditional Sections

- Hide sections (tables, headings) when there's no data rather than showing empty tables
- Example: "Observed File Names" and "Yara Hits" sections only render when results exist

## Environment Variables

All read via `getenv()` in `server_includes.php` and `server_registration.php`.

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
