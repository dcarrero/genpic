# Code conventions

## Language

- Classes, methods, database columns and route names are in **English**
  (Laravel convention).
- Local variables, code comments and internal docs are mostly in
  **Spanish**: the project was built by a Spanish team for the Spanish market
  first. New contributions can use either language.
- User-facing text never goes in a template: it lives in `lang/{locale}/ui.php`
  in all five languages.

## Layout

```
app/
├── Console/Commands/    # Artisan commands (Stripe sync, ban sync, previews…)
├── Filament/            # Admin panel: resources, pages, widgets
├── Http/Controllers/    # Thin controllers
├── Http/Middleware/     # Locale, IP ban, admin access, security headers…
├── Jobs/                # Everything slow goes through the queue
├── Livewire/            # PhotoUploader, StyleSelector, PreviewPanel
├── Mail/                # One Mailable per email
├── Models/
└── Services/            # Business logic
    ├── ContentModeration/
    └── ImageGeneration/ # Provider chain behind an interface
config/genpic.php        # Project settings, all overridable from .env
config/image_providers.php
```

## Patterns

- **Thin controllers, logic in services.** Controllers validate, call a
  service and return a view or a redirect.
- **Providers behind an interface.** A new AI provider is one class that
  implements `ImageProviderInterface` and one entry in
  `config/image_providers.php`.
- **Slow work goes to the queue.** Generation, watermarking, formats and
  bulk email are jobs; Livewire polls the status every 3 seconds.
- **Signed URLs everywhere.** Downloads, session access and private images go
  through signed routes. There are no public bucket URLs.
- **Config from `.env`.** No PHP file needs editing to deploy. One exception:
  `bootstrap/app.php` runs before the configuration loads, so the trusted
  proxy list there is a literal.

## Naming

Database:

- Plural tables (`generation_sessions`), foreign keys `{singular}_id`.
- Booleans without an `is_` prefix (`active`, `face_detected`).
- Event timestamps as `{action}_at` (`paid_at`, `purged_at`).
- Money as `{name}_cents` integers.
- `uuid` column for anything that appears in a public URL, `id` for internal
  relations.

PHP:

- `{Name}Service`, `{Name}Provider`, `{Action}{Object}Job`, `{Context}Mail`.

Routes:

- Public routes are named and localized; build URLs with `locale_route()`,
  never by concatenating a locale prefix.
- Webhooks and technical endpoints are in English and unprefixed
  (`/webhooks/stripe`).

## Things to avoid

- Business logic in controllers or Blade templates.
- Public URLs to the bucket.
- Deleting `orders` or payment records: only files are purged.
- Floats for prices.
- Naming living artists in style prompts (see
  [ARTISTIC_PROMPTS.md](ARTISTIC_PROMPTS.md)).
- Queuing a generation without face detection and moderation first.
- Sending the original photo back to the browser.
- Removing `'unsafe-eval'` from the CSP: Alpine needs it, and without it the
  whole site stops working.

## Tests

`php artisan test` runs on SQLite in memory with array cache, mail and queue
drivers, so it needs neither MariaDB nor Redis. CI runs it on every push
(`.github/workflows/tests.yml`).
