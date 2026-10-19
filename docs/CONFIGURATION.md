# Configuration

Everything is configured through `.env`. No PHP file needs editing for a normal
deployment: `config/genpic.php`, `config/image_providers.php`,
`config/services.php` and `config/filesystems.php` read their values with
`env()`.

Copy `.env.example` to `.env` and go through the groups below. "Default" means
the value used when the variable is missing from `.env` (from the `config/`
file), which is not always the value written in `.env.example`; both are given
where they differ.

## Development without paid services

You can run the whole site locally without any external account. What happens
when a key is missing:

| Missing | Behaviour |
|---------|-----------|
| AI keys | Everything browses fine, but generation fails. Locally you need `FAL_AI_KEY` even if another provider generates: in `local` the photo is uploaded to fal.ai's CDN to get a URL the provider can fetch (`ImageCdnUploader`). |
| `STRIPE_SECRET` **empty** | Checkout is simulated: the order is marked as paid straight away and you land on the success page. The placeholder `sk_test_...` in `.env.example` is *not* empty, so blank the three Stripe lines to use this mode. |
| Wasabi / S3 keys | Not needed. In `local` and `testing` files go to the local disk (`storage/app/private/`); any other `APP_ENV` uses the `wasabi` disk (`StorageService::disk()`). |
| `HUGGINGFACE_TOKEN` | The NSFW check is skipped. It also fails open (lets the photo through) when the Hugging Face API errors. |
| SMTP | With Docker, mail goes to Mailpit. Without Docker, `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`. |

The simulated checkout only triggers when there is no Stripe secret at all. In
production, make sure `STRIPE_SECRET` is set, or orders will be fulfilled for
free.

## Application

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `APP_NAME` | `GenPic` | Brand name used in titles and emails. |
| `APP_ENV` | `local` | `local` uses the local disk and opens Horizon without login. Use `production` live. |
| `APP_KEY` | empty | Generate with `php artisan key:generate`. |
| `APP_DEBUG` | `true` | `false` in production. |
| `APP_TIMEZONE` | `Europe/Madrid` | The schedule (`routes/console.php`) also pins its jobs to Madrid time. |
| `APP_URL` | `http://localhost:8000` | Absolute URLs, sitemap, `robots.txt`, links in emails and the trusted host. Docker overrides it with `http://localhost:8101`. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `es` | Spanish is the unprefixed default language. |
| `APP_LOCALES` | `es,en,fr,pt,it` | Active languages. Each one needs `lang/{code}/` and an entry in `genpic.locale_map` (see [CUSTOMIZING.md](CUSTOMIZING.md#add-a-language)). |
| `MAINTENANCE_MODE` | `false` | `true` returns 503 to everyone except `ADMIN_ALLOWED_IPS`. |
| `CACHE_PUBLIC_PAGES_HOURS` | `24` (default `168`) | Full-HTML cache for the landing, styles and gallery pages. `0` disables it. |

## Company and legal

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `COMPANY_NAME` | `Example Company S.L.` | Footer, emails, schema.org and the copyright line written into image metadata (IPTC/XMP). |
| `CONTACT_EMAIL` | `hello@example.com` | Contact address shown on the site and in emails. |
| `WATERMARK_TEXT` | `GenPic preview` | Text repeated in the diagonal grid over unpaid previews. |

The legal pages themselves are translation strings, not variables: see
[CUSTOMIZING.md](CUSTOMIZING.md#legal-pages).

## Admin panel

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `ADMIN_PATH` | `admin` | URL of the Filament panel. Changing it only hides the panel. |
| `ADMIN_EMAILS` | `admin@example.com` | Comma-separated. Only these users can enter the panel and Horizon (`User::canAccessPanel`). |
| `ADMIN_ALLOWED_IPS` | empty | Comma-separated allowlist for the panel. Empty means no restriction, which is fine locally and wrong in production. These IPs also skip `MAINTENANCE_MODE` and IP bans. |

## Database, cache and queues

Standard Laravel variables (`DB_*`, `REDIS_*`, `CACHE_STORE`,
`SESSION_DRIVER`, `QUEUE_CONNECTION`). The app expects **MariaDB 10.11+** and
**Redis**: queues run on Redis through Horizon, and IP bans and provider
cooldowns live in Redis. The defaults in `.env.example` match the Docker
services (`mariadb`, `redis`).

`CACHE_PREFIX` and `REDIS_PREFIX` only matter if several apps share one Redis.

## Storage

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `FILESYSTEM_DISK` | `local` | Laravel's default disk. The app's own files ignore it: see the table at the top. |
| `WASABI_KEY`, `WASABI_SECRET` | empty | Access keys for the bucket. |
| `WASABI_REGION` | `eu-central-1` | |
| `WASABI_BUCKET` | `your-bucket` | Must be **private**. The app serves files through signed URLs and never makes objects public. |
| `WASABI_ENDPOINT` | `https://s3.eu-central-1.wasabisys.com` | Any S3-compatible endpoint works (AWS S3, Cloudflare R2, MinIO…), despite the name. |

Keys follow `{type}/{session_uuid}/{timestamp}_{hash}.{ext}`, with `type` one
of `originals`, `generations`, `watermarked`, `exports`.

## Stripe

| Variable | What it does |
|----------|--------------|
| `STRIPE_KEY` | Publishable key. |
| `STRIPE_SECRET` | Secret key. Empty means simulated checkout (see above). |
| `STRIPE_WEBHOOK_SECRET` | Signing secret of the webhook endpoint. |

The checkout charges in **EUR** with tax-inclusive prices and
`automatic_tax` enabled, so **Stripe Tax must be activated** in your Stripe
account or session creation fails. Product prices are sent inline
(`price_data`), so there are no Stripe products or price IDs to create.

Webhook endpoint: `https://your-domain/webhooks/stripe`, events
`checkout.session.completed` and `checkout.session.async_payment_succeeded`.
The `stripe:sync-orders` command runs every five minutes and recovers payments
whose webhook never arrived.

## AI providers

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `AI_PROVIDER_CHAIN` | `fal_ai,openrouter,novita,evolink` | Order in which providers are tried. Disabled or keyless providers are skipped. |
| `FAL_AI_KEY`, `FAL_AI_ENABLED` | empty, `true` | fal.ai, SeedReam v5 Lite Edit. Also used for upscaling and, locally, as CDN. |
| `OPENROUTER_API_KEY`, `OPENROUTER_ENABLED` | empty, `false` (default `true`) | OpenRouter. Docker sets it to `true`. |
| `OPENROUTER_DEFAULT_MODEL` | `bytedance-seed/seedream-4.5` | |
| `OPENROUTER_IMAGE_SIZE` | not set (default `4K`) | |
| `EVOLINK_API_KEY`, `EVOLINK_ENABLED` | empty, `false` | Evolink, SeedReam 4.5. |
| `NOVITA_API_KEY`, `NOVITA_ENABLED` | empty, `false` | Novita, SeedReam 4.5. |
| `UPSCALE_PROVIDER` | `fal_ai` | Real-ESRGAN upscale for the largest format. |
| `DAILY_COST_BUDGET_USD` | `50` | See below. |

Models, timeouts and the estimated cost per image of each provider are in
`config/image_providers.php`. A provider that times out sleeps for 5 minutes,
one that errors for 2; the next one in the chain is tried immediately. Every
call is logged in `provider_logs` with its estimated cost.

`DAILY_COST_BUDGET_USD` is **not a hard cap**. Once the day's logged spend
passes it, the chain is reordered cheapest first and generation carries on. If
you need a real limit, set it in each provider's dashboard.

## Generation, limits and retention

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `RATE_LIMIT_GENERATIONS_PER_IP` | `3` | Free generations per IP... |
| `RATE_LIMIT_PERIOD_HOURS` | `24` | ...in this window. |
| `GENERATION_SESSION_TTL_DAYS` | `14` | How long a session and its files live before the nightly purge. Paid orders are never purged. |
| `DOWNLOAD_LINK_TTL_HOURS` | `72` (default `336`) | Lifetime of signed download links. |
| `FILE_PURGE_GRACE_DAYS` | `1` | Extra margin before files are deleted. |
| `REQUIRE_EMAIL_BEFORE_GENERATION` | `false` | `true` asks for the email before generating. |
| `REQUIRE_PIN_VERIFICATION` | `false` | `true` adds a 6-digit PIN sent by email; with the option above, the email becomes mandatory. |
| `FACE_DETECTION_ENABLED` | `true` | Requires at least one face (face-api.js, in the browser) before upload. |
| `FREE_WEB_BASIC_ENABLED` | not set (`false`) | Lets people download the smallest format for free, with a courtesy mark. |

## Content moderation

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `CONTENT_MODERATION_ENABLED` | `true` | Checks every upload with a Hugging Face NSFW classifier before any paid generation. |
| `CONTENT_MODERATION_PROVIDER` | `huggingface` | Only provider implemented. |
| `HUGGINGFACE_TOKEN` | empty | Hugging Face access token. The free tier was enough in production. |
| `CONTENT_MODERATION_BAN_HOURS` | `72` | IP ban after a violation. |
| `MODERATION_THRESHOLD_NUDITY` | not set (`0.60`) | Score above which a photo is rejected. |

## Email

Standard Laravel `MAIL_*` variables. Any SMTP server works. The app sends the
portrait link, PIN codes, download links, expiry warnings, abandoned-session
and upgrade emails, and admin alerts (provider failures, moderation).

## Analytics

`GOOGLE_ANALYTICS_ID` (not in `.env.example`) is a fallback: the ID is
normally set in the admin under *Site settings*, which also lets you add
arbitrary head and body scripts. Google Analytics runs in Consent Mode with
analytics and ads storage denied until the visitor accepts the cookie banner.

## Closed mode

What genpic.com ran in its last weeks. Leave it off unless you are shutting a
site down.

| Variable | `.env.example` | What it does |
|----------|----------------|--------------|
| `CLOSED_MODE` | `false` | `true` makes `/create` and `/checkout` return 410 with the closing page, shows a notice on every page and silences the abandoned-session and upgrade emails. Gallery, styles, landings, legal pages, `/my-downloads` and signed downloads keep working. |
| `CLOSED_MODE_DATE` | `2026-09-30` | Date shown on the closing page. |
| `CLOSED_DOWNLOADS_UNTIL` | empty | Last day to recover portraits. Empty means closing date plus `GENERATION_SESSION_TTL_DAYS`. |
| `OPENSOURCE_REPO_URL` | empty | If set, the closing page links to it. |

The list of other projects recommended on the closing page is in
`config/genpic.php` (`closing.projects`); replace it with yours.
