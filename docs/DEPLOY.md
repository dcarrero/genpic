# Deploying to production

How to run GenPic on your own server. genpic.com ran on a single VPS
managed with RunCloud, behind LiteSpeed and Cloudflare, with atomic
deployments. Nothing here depends on RunCloud: any VPS with Nginx or
LiteSpeed, a process supervisor and cron will do. RunCloud specifics are
marked as such.

For a local install, see [INSTALL.md](INSTALL.md). For every variable, see
[CONFIGURATION.md](CONFIGURATION.md).

## Server requirements

- PHP 8.3 or 8.4 with `bcmath`, `gd`, `imagick`, `intl`, `pcntl`,
  `pdo_mysql`, `redis` and `zip` (CI runs on 8.4)
- MariaDB 10.11+
- Redis 7+
- Composer 2
- Node.js 20+, only to build the assets
- Nginx or LiteSpeed, with HTTPS
- A process supervisor (supervisord or systemd) for Horizon
- cron

Outside the server: a **private** S3-compatible bucket, an SMTP account, a
Stripe account with Stripe Tax enabled, and at least one AI provider key with
credit. The web root is `public/`.

## Directory layout (atomic deployments)

Each deploy goes into a new folder under `releases/`, and a `current` symlink
points at the live one. Whatever must survive a deploy lives in `shared/`:

```
/var/www/genpic/
├── current -> releases/20260323120000   ← live release (web root: current/public)
├── releases/
│   ├── 20260323120000/
│   └── ...
└── shared/                              ← persists across deploys
    ├── .env                             ← configuration, never in git
    └── storage/
        ├── app/                         ← local files (gallery cache, temp files)
        ├── framework/{cache,sessions,views}/
        └── logs/
```

With `APP_ENV=production`, uploads, generations, previews and exports always
go to the S3 bucket, so `shared/storage/app/` stays small.

RunCloud creates exactly this layout under `/home/runcloud/webapps/{app}/`.

## First deploy

### Option A: interactive script

`scripts/setup.sh` is written for the layout above. Copy it next to
`current/` (outside the web root) and run it once:

```bash
cp scripts/setup.sh /var/www/genpic/
cd /var/www/genpic
chmod +x setup.sh
./setup.sh
```

It asks for every value, writes `shared/.env` (keeping existing values if you
run it again), generates `APP_KEY`, creates the shared symlinks, runs
`deploy:seed` (migrations, every seeder and the admin user), builds the
assets if `npm` is available and caches config, routes and views. Its prompts
are in Spanish.

The `.env` it writes has `MAINTENANCE_MODE=true`, so only
`ADMIN_ALLOWED_IPS` can see the site. Set it to `false` when you're ready.

### Option B: by hand

```bash
APP=/var/www/genpic
mkdir -p $APP/shared/storage/{app,framework/{cache/data,sessions,views},logs}
cp .env.example $APP/shared/.env        # then edit it: see CONFIGURATION.md

cd $APP/current
ln -nfs $APP/shared/.env .env
rm -rf storage/app storage/framework storage/logs
ln -nfs $APP/shared/storage/app storage/app
ln -nfs $APP/shared/storage/framework storage/framework
ln -nfs $APP/shared/storage/logs storage/logs

composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan deploy:seed --admin-email=you@example.com
npm ci && npm run build
php artisan storage:link
```

### Admin password

Buyers have no passwords, and `deploy:seed` creates the admin user without
one. Give it a password before logging in at `/{ADMIN_PATH}`:

```bash
php artisan tinker --execute="\$u = App\Models\User::where('email', 'you@example.com')->first(); \$u->forceFill(['password' => bcrypt('a-long-password')])->save();"
```

The same email must be in `ADMIN_EMAILS`, and your IP in
`ADMIN_ALLOWED_IPS`.

## Deploy script

Run this inside each new release before the `current` symlink switches
(in RunCloud: *Web Application → Git → Deployment script*):

```bash
#!/bin/bash
set -e

RELEASE_PATH="$1"
SHARED_PATH="/var/www/genpic/shared"

cd "$RELEASE_PATH"

# Point the release at the shared files
ln -nfs "$SHARED_PATH/.env" .env
rm -rf storage/app storage/framework storage/logs
ln -nfs "$SHARED_PATH/storage/app" storage/app
ln -nfs "$SHARED_PATH/storage/framework" storage/framework
ln -nfs "$SHARED_PATH/storage/logs" storage/logs

# Dependencies and assets
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

# Laravel
php artisan storage:link --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart the queue workers so they load the new code
php artisan horizon:terminate
```

Don't call `env()` outside the `config/` files: after `config:cache` it
returns `null`. The code follows that rule; keep it if you add features.

## Scheduler

One cron entry:

```
* * * * * cd /var/www/genpic/current && php artisan schedule:run >> /dev/null 2>&1
```

What it runs (source of truth: `routes/console.php`):

| When | What |
|------|------|
| Every 5 minutes | `stripe:sync-orders`: recovers payments whose webhook never arrived |
| Hourly | Abandoned-session and upgrade emails (skipped in closed mode) |
| Hourly | `moderation:sync-bans`: copies active IP bans from the database to Redis |
| 03:00 Madrid | `PurgeExpiredFilesJob`: deletes expired files, never those of paid orders |
| 09:00 Madrid | Expiry warning email, two days before a session's files are purged |

## Queue workers (Horizon)

Horizon runs every queue. Keep one process alive with your supervisor:

```ini
[program:genpic-horizon]
command=php /var/www/genpic/current/artisan horizon
user=www-data
numprocs=1
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/genpic/shared/storage/logs/horizon.log
stopwaitsecs=3600
```

It starts two supervisors (`config/horizon.php`): `default` for generation,
formats and email (up to 5 processes in production, to stay under fal.ai's
concurrency limit) and `maintenance` for the purge (1 process). The dashboard
is at `/horizon`, open only to `ADMIN_EMAILS`.

## Stripe webhook

In the Stripe Dashboard, under *Developers → Webhooks*:

- URL: `https://your-domain/webhooks/stripe`
- Events: `checkout.session.completed`,
  `checkout.session.async_payment_succeeded`
- Copy the signing secret to `STRIPE_WEBHOOK_SECRET`

Enable **Stripe Tax** as well: checkout sessions are created with
`automatic_tax` and fail without it.

## Proxies and client IPs

The rate limit and the IP bans depend on the real client IP. `bootstrap/app.php`
trusts `X-Forwarded-*` headers only from Cloudflare's published IP ranges and
from localhost, because genpic.com sat behind Cloudflare. If you use another
CDN or load balancer, replace that list with its ranges; if you use none, the
list does no harm. The list is a literal on purpose: that code runs before
the configuration is loaded, so `env()` and `config()` don't work there.

Trusted hosts: Laravel accepts the host of `APP_URL` and its subdomains, plus
`localhost`.

## Pre-launch checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` with `https://`
- [ ] `COMPANY_NAME`, `CONTACT_EMAIL` and the legal pages rewritten ([CUSTOMIZING.md](CUSTOMIZING.md#legal-pages))
- [ ] `ADMIN_EMAILS`, `ADMIN_ALLOWED_IPS` and an admin password
- [ ] Database migrated and seeded
- [ ] The admin's *System health* page all green (database, Redis, bucket, each AI provider, Stripe, email, Hugging Face)
- [ ] `STRIPE_SECRET` set (empty means simulated, free orders), webhook configured, Stripe Tax enabled
- [ ] At least one AI provider with credit, `HUGGINGFACE_TOKEN` for moderation
- [ ] cron and Horizon running
- [ ] HTTPS
- [ ] `MAINTENANCE_MODE=false`
- [ ] One full run: upload → generate → pay → download

## Checking a deploy

```bash
# The app answers and serves the new CSS (the hash changes on each build)
curl -s https://your-domain/ | grep -o 'app-[A-Za-z0-9_-]*\.css'

# Fonts are self-hosted: no requests to Google
curl -s https://your-domain/build/assets/app-<hash>.css | grep -c 'gstatic\|googleapis'          # 0
curl -so /dev/null -w '%{http_code}\n' https://your-domain/fonts/source-serif-4-var-latin.woff2   # 200

# The version in the footer matches the CHANGELOG
curl -s https://your-domain/ | grep -o 'v[0-9.]*'

# Security headers
curl -sI https://your-domain/ | grep -i 'content-security-policy\|strict-transport'
```

The version lives in `config/app.php` (`'version'`), not in `.env`. Bump it in
the same commit as `CHANGELOG.md`: it is easy to forget.

## Fonts

Fonts are served from `public/fonts/`, never from Google Fonts: a remote
`@import` sends every visitor's IP to Google, which a German court ruled a
GDPR breach in 2022. The `@font-face` rules are in `resources/css/app.css`,
and `public/fonts/README.md` explains how to regenerate the files. Both
families are under the SIL Open Font License 1.1.

If you ever go back to a CSS `@import`, it must be the first line of the file.
Placed after the `@tailwind` directives, the build drops it without a warning.

## Content Security Policy

`app/Http/Middleware/SecurityHeaders.php` sends two policies:

- **Enforced**: `base-uri`, `object-src`, `frame-ancestors`, `form-action`.
  They don't depend on which scripts load, so they can't break anything.
- **Report-only**: the full policy, including `script-src`. It isn't enforced
  because the admin can inject arbitrary scripts (*Site settings*, head and
  body scripts), and an allowlist would break them silently.

`'unsafe-eval'` must stay in `script-src`: the Alpine.js bundled with Livewire
needs it, and without it the whole site stops working.

To enforce the full policy, review the violations reported in the browser
console, decide what to do with the admin scripts and move the directives from
`$reportOnly` to `$enforced`. The middleware never overrides a CSP the
response already carries: `SecureImageController` serves images with
`default-src 'none'`.

## Rollback

Point `current` back at the previous release (in RunCloud: *Deployments →
previous release → Activate*) and restart Horizon. The database is not rolled
back; if a migration has to be undone:

```bash
php artisan migrate:rollback --step=1
```
