# Architecture

GenPic is a single Laravel 11 application: server-rendered Blade pages, a few
Livewire components for the interactive funnel, Redis queues for the slow work
and a Filament panel for operations. There is no SPA and no separate API.

## Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Backend | Laravel 11, PHP 8.3+ (CI runs 8.4) | |
| Frontend | Blade + Livewire 3 + Alpine + Tailwind CSS 3 | Built with Vite |
| Admin | Filament 3 | Path set by `ADMIN_PATH`, IP allowlist + admin emails |
| Database | MariaDB 10.11+ (utf8mb4) | Tests use SQLite in memory |
| Cache / queues | Redis 7 + Horizon | Queues `default` and `maintenance` |
| Object storage | Any S3-compatible bucket (production used Wasabi) | Local disk in development |
| Images | Intervention Image v3 + native Imagick | Watermark, formats, metadata |
| Payments | Stripe Checkout + webhooks, Stripe Tax | EU VAT (OSS) handled by Stripe |
| AI generation | fal.ai, OpenRouter, Novita, Evolink | Provider chain with fallback |
| Moderation | Hugging Face Inference API (NSFW classifier) | Runs before any paid AI call |
| Face detection | face-api.js in the browser (`public/models/`) | Blocks the upload if no face is found |
| Email | Any SMTP server | Mailpit in Docker |

## Main flow

```
Landing → pick style → upload photo
  → face detection (browser) + NSFW check (server)
  → GeneratePortraitJob (provider chain) → ApplyWatermarkJob
  → Livewire polls every 3 s → watermarked preview
  → email capture → session link by email (signed URL, 14-day TTL)
  → choose product → Stripe Checkout → webhook (signature checked, idempotent)
  → OrderFulfillmentService → [upsell jobs] → GenerateFormatsJob
  → "download ready" email → signed download links
  → PurgeExpiredFilesJob deletes the files after the TTL
```

`stripe:sync-orders` runs every five minutes and reconciles any paid Checkout
session whose webhook never arrived.

## Data model

```
users (no password: access only through signed URLs sent by email)
  └── generation_sessions (1:N) — one per uploaded photo; UUID in public URLs
        ├── generations (1:N) — one per AI attempt or extra style
        │     └── provider_logs — cost, latency and result per call
        ├── orders (1:N)
        │     └── order_items (1:N) → products → formats
        └── purge_queue — files scheduled for deletion

artistic_styles ← generations, generation_sessions
coupons, gallery_examples, testimonials, site_settings
mail_campaigns → mail_sends          (email campaigns with GDPR opt-out)
email_sequences                      (abandonment and upgrade emails)
content_violations, ip_bans          (moderation)
```

Decisions behind it:

- **No passwords.** A buyer never creates an account. Everything hangs off the
  session UUID and signed links, which removes the login flow and its support
  load.
- **`generation_sessions` is the hub.** Everything a visitor does with one
  photo lives under one session, so purging and access checks have a single
  place to look.
- **Prices are integer cents**, with separate EUR and USD columns. No floats.
- **Orders are never deleted.** The purge job removes files, never billing
  records, and it skips any session with a paid order.
- **Translatable content is JSON** (style names, products, gallery titles)
  instead of one column per language.

## AI provider chain

`app/Services/ImageGeneration/` holds one class per provider behind
`ImageProviderInterface`. `ImageGenerationService` walks the chain set in
`AI_PROVIDER_CHAIN`:

- A provider that times out goes into a 5-minute cooldown in Redis; any other
  error gives it a 2-minute cooldown. The next provider is tried straight away.
- Every call writes a `provider_logs` row with its estimated cost.
- Once the day's spend passes `DAILY_COST_BUDGET_USD`, the chain is reordered
  cheapest first.
- If a provider refuses the image for content policy reasons, a
  `ContentPolicyException` stops the chain: trying the next provider with the
  same photo would only spend more money on the same refusal.
- The admin gets an email when every provider in the chain fails.

`PromptBuilder` puts together the style prompt, the face-preservation rules and
the orientation hint. The style prompts and the reasoning behind them are in
[ARTISTIC_PROMPTS.md](ARTISTIC_PROMPTS.md).

## Content moderation

Two layers, both in `app/Services/ContentModeration/`:

1. Before any generation, the photo goes to a Hugging Face NSFW classifier.
   This costs nothing per image and stops the bad uploads before they reach a
   paid provider.
2. If a provider rejects the image anyway, the job raises
   `ContentPolicyException`.

Both paths ban the IP (72 h by default) in Redis and the database, email the
admin, and return a generic 503 that gives nothing away. Bans are synced from
the database to Redis every hour (`moderation:sync-bans`).

## Watermark and image delivery

- Unpaid images get a dense diagonal text grid (see `genpic.watermark` in
  `config/genpic.php`).
- The original photo is never sent back to the browser, only the watermarked
  preview.
- Private files are streamed by `SecureImageController` (signed URL, referer
  check, `Content-Security-Policy: default-src 'none'`) or served through
  short-lived signed S3 URLs. Bucket objects are never public.
- Paid formats carry IPTC/XMP metadata with the copyright line
  (`ImageMetadataService`).

Storage keys follow `{type}/{session_uuid}/{timestamp}_{hash}.{ext}`, where
`type` is one of `originals`, `generations`, `watermarked` or `exports`.

## Languages and URLs

Five locales (es, en, fr, pt, it). Spanish has no prefix; the others use
`/en/`, `/fr/`… **Slugs are translated** (`/fotos-de-familia` →
`/en/family-photos`), so the URL of a page in another language can never be
built by gluing the prefix onto the current path. Every localized route has a
name, and both the `hreflang` tags and the language switcher resolve the other
languages through route names (`locale_route()` in `app/helpers.php`). The
sitemap and `robots.txt` are generated by routes from `APP_URL`.

## Queues and schedule

| Queue | Jobs |
|-------|------|
| `default` | Portrait generation, watermark, formats, upsell portraits, campaign batches |
| `maintenance` | File purge, abandonment and upgrade emails |

Schedule (`routes/console.php`): file purge at 03:00 Europe/Madrid, Stripe
reconciliation every 5 minutes, expiry warning email daily, abandonment and
upgrade emails hourly, IP ban sync hourly.

## Admin panel

Filament resources for styles, sessions (with a private image viewer), orders,
products, users (with GDPR anonymization), provider logs, gallery,
testimonials, email sequences, campaigns, content violations and IP bans.
Nine dashboard widgets cover revenue, conversion, AI cost and provider health,
and a System Health page pings every external service.

## Closed mode

`CLOSED_MODE=true` switches the site into the state genpic.com ended in:
creation and checkout return 410 with a closing page, a notice appears on every
page, and downloads keep working until `CLOSED_DOWNLOADS_UNTIL`. It is off by
default.
