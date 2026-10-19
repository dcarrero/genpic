# Installation

[Leer en español](INSTALL-ES.md)

How to get GenPic running on your machine. For a production server, see
[DEPLOY.md](DEPLOY.md); for every setting, [CONFIGURATION.md](CONFIGURATION.md).

## With Docker (recommended)

You only need Docker with Compose v2. The stack has five containers: the app
(PHP 8.4 with `php artisan serve`), Horizon for the queues, MariaDB 10.11,
Redis 7 and Mailpit.

### 1. Start it

```bash
git clone https://github.com/dcarrero/genpic.git
cd genpic
cp .env.example .env
docker compose up -d --build
```

The first start takes a few minutes: the `app` container runs
`composer install`, `npm install && npm run build` and
`php artisan migrate --seed` before it starts serving. Follow it with:

```bash
docker compose logs -f app
```

When you see the seeders finish and the server start, generate the
application key:

```bash
docker compose exec app php artisan key:generate
```

| What | Where |
|------|-------|
| Site | http://localhost:8101 |
| Mailpit (every email the app sends) | http://localhost:8025 |
| Admin panel | http://localhost:8101/admin |
| Horizon (queues) | http://localhost:8101/horizon |
| MariaDB from the host | `127.0.0.1:33061`, user `genpic`, password `secret` |
| Redis from the host | `127.0.0.1:63791` |

The seed loads 73 styles in nine categories, the products, 36 gallery
examples, testimonials and email sequences. Every start runs
`migrate --seed` again; the seeders are idempotent.

### 2. Create the admin user

Buyers have no passwords, so the admin is created by hand. `.env.example`
already lists `admin@example.com` in `ADMIN_EMAILS`:

```bash
docker compose exec app php artisan tinker --execute="\$u = App\Models\User::firstOrNew(['email' => 'admin@example.com']); \$u->forceFill(['password' => bcrypt('change-me'), 'is_admin' => true])->save();"
```

Log in at http://localhost:8101/admin with `admin@example.com` and
`change-me`. To use another email, put it in `ADMIN_EMAILS` too. The admin
panel is in Spanish.

### 3. Make portraits

Without AI keys you can browse the whole site, but generation fails. To
generate locally:

1. Add `FAL_AI_KEY` to `.env`. It is required locally even if another
   provider generates the image: in `local` the photo is uploaded to fal.ai's
   CDN to get a URL the provider can fetch. `OPENROUTER_API_KEY` is optional
   (Docker enables OpenRouter).
2. Either add a `HUGGINGFACE_TOKEN` or set `CONTENT_MODERATION_ENABLED=false`.
   Without a token the NSFW check is skipped anyway.
3. Recreate the containers so they pick up the keys (Compose passes
   `FAL_AI_KEY` as an environment variable):

   ```bash
   docker compose up -d
   ```

Each portrait costs a few cents on the provider's side; see the table in the
[README](../README.md#external-services-and-what-they-cost).

### 4. Payments

To try the checkout without Stripe, **empty** the three Stripe lines in
`.env`:

```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

With no secret, the checkout is simulated: the order is marked as paid at
once and the paid formats are generated as if Stripe had confirmed it. The
placeholders in `.env.example` (`sk_test_...`) are not empty, so the app
would try to call Stripe with them and fail.

To test real Stripe, use your test keys, enable Stripe Tax in test mode and
forward the webhook with the Stripe CLI:

```bash
stripe listen --forward-to localhost:8101/webhooks/stripe
```

and put the `whsec_...` it prints in `STRIPE_WEBHOOK_SECRET`.

### 5. Scheduled tasks

The Compose stack doesn't run the scheduler. You only need it to test the
nightly purge, the expiry and abandoned-session emails or the Stripe
reconciliation:

```bash
docker compose exec app php artisan schedule:work
```

### 6. Full-size images (optional)

The repo ships the style samples at 1200 px to keep the clone small. The
original 2048 px set (~340 MB) is published as a release asset:

```bash
./scripts/fetch-samples.sh
```

It overwrites `public/img/` with the full set. Set `GENPIC_IMAGES_URL` to
download it from somewhere else.

## Without Docker

You need:

- PHP 8.3 or 8.4 with `bcmath`, `gd`, `imagick`, `intl`, `pcntl`,
  `pdo_mysql`, `redis` and `zip`. `imagick` (with ImageMagick) builds the
  watermark and the export formats; `pcntl` is needed by Horizon.
- Composer 2
- Node.js 20+ and npm
- MariaDB 10.11+ (MySQL 8 should work, but production ran on MariaDB)
- Redis 7+

Then:

```bash
git clone https://github.com/dcarrero/genpic.git
cd genpic
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Edit `.env` so it points at your local services (the defaults are the Docker
host names):

```
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_DATABASE=genpic
DB_USERNAME=your_user
DB_PASSWORD=your_password
REDIS_HOST=127.0.0.1
```

Create the database (`utf8mb4`), then:

```bash
php artisan migrate --seed
php artisan serve                  # http://localhost:8000
php artisan horizon                # queues, in another terminal
php artisan schedule:work          # optional, in another terminal
```

Generation runs in the queue, so without Horizon (or `php artisan
queue:work`) the preview stays on "generating" forever. Mail goes to
`storage/logs/laravel.log` because `.env.example` sets `MAIL_MAILER=log`.

For front-end work, `npm run dev` gives you Vite with hot reload instead of
`npm run build`. `composer dev` starts the server, a queue listener, the log
viewer and Vite together.

Steps 2 to 6 of the Docker section apply too, without the
`docker compose exec app` prefix.

## Running the tests

The suite runs on SQLite in memory with Redis mocked, so it needs neither
MariaDB nor Redis. It does need an `APP_KEY`:

```bash
cp .env.example .env               # if you don't have one yet
php artisan key:generate
php artisan test
```

or, with Docker:

```bash
docker compose exec app php artisan test
```

All 178 tests should pass. CI runs them on PHP 8.4 on every push
(`.github/workflows/tests.yml`).

## Common problems

**The site doesn't answer on port 8101 right after `up`.** The first start is
still installing dependencies. Check `docker compose logs -f app`.

**`docker compose ps` shows `queue` restarting.** The Horizon container shares
the code with `app` and crashes until `composer install` has created
`vendor/`. It recovers on its own; if it doesn't, run
`docker compose restart queue`.

**"The container name /genpic-app is already in use".** The containers have
fixed names (`genpic-app`, `genpic-db`…). Remove the old ones, or change
`container_name` in `docker-compose.yml`.

**A port is already taken.** Change the host side of the mapping in
`docker-compose.yml` (`"8101:8000"`, `"8025:8025"`…). If you change the app
port, change `APP_URL` in `docker-compose.yml` as well.

**"No application encryption key has been specified".** Run
`php artisan key:generate` (with the `docker compose exec app` prefix if you
use Docker).

**The preview never finishes.** Generation is a queued job. Check that
Horizon is running (`/horizon`), that the provider key is set and that the
containers were recreated after adding it. Failures are in
`storage/logs/laravel.log` and in the admin under *Sistema → Logs de IA*.

**"fal.ai key required for CDN upload in local environment".** Add
`FAL_AI_KEY`; see step 3.

**Style pages have no pictures.** The images for that style are missing from
`public/img/styles/`. Run `./scripts/fetch-samples.sh`, or generate them as
described in [CUSTOMIZING.md](CUSTOMIZING.md#add-an-artistic-style).

**Changes to translations or styles don't show up.** Public pages are cached
for `CACHE_PUBLIC_PAGES_HOURS`. Run `php artisan optimize:clear`, or set the
variable to `0` while you work.
