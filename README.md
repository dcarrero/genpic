# GenPic

**An AI portrait SaaS, finished, tested and run in production, released after
it failed commercially.** Laravel 11 · Livewire 3 · Filament 3 · Stripe · fal.ai.

[Leer en español](README-ES.md)

![GenPic home page](docs/screenshots/01-home-hero-en.jpg)

## What it was, and what happened

GenPic took a photo of you, your family or your friends and turned it into a
painted portrait: Renaissance oil, ukiyo-e, anime, a 1920s studio shot, a
LinkedIn headshot… 73 styles in nine categories. You uploaded a photo, picked a
style, saw a watermarked preview in under a minute, and paid only if you wanted
the clean file.

It ran at genpic.com from March to October 2026, in five languages, taking
real payments through Stripe with EU VAT handled. People generated plenty of
free previews. **Nobody bought one.** Zero sales. Sign-ups and checkout were
closed in September 2026 and the domain was retired in October.

The code, though, was finished, tested and running in production, and binning
it seemed a waste. So here it is, MIT-licensed. If you're building anything
with AI image generation, paid downloads or a multilingual Laravel site, there's
probably something here you can take.

## Screenshots

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/05-style-detail-anime-en.jpg" alt="Before/after slider on a style page"></td>
    <td width="50%"><img src="docs/screenshots/07-gallery-en.jpg" alt="Gallery of before/after examples"></td>
  </tr>
  <tr>
    <td><b>Style page</b>: drag to compare the photo and the portrait</td>
    <td><b>Gallery</b>: 36 before/after pairs</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/09-landing-photo-to-painting-en.jpg" alt="Photo to painting landing page"></td>
    <td><img src="docs/screenshots/30-admin-dashboard.jpg" alt="Admin dashboard"></td>
  </tr>
  <tr>
    <td><b>Landing pages</b>: ten of them, each with its own layout</td>
    <td><b>Admin</b>: revenue, conversion and AI cost (the zeros are real; the admin panel is in Spanish)</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/32-admin-ai-configuration.jpg" alt="AI provider configuration"></td>
    <td><img src="docs/screenshots/38-admin-style-edit.jpg" alt="Editing a style in the admin"></td>
  </tr>
  <tr>
    <td><b>AI providers</b>: fallback chain, cooldowns and cost per image</td>
    <td><b>Styles</b>: translated labels and the prompts behind each one</td>
  </tr>
</table>

<table>
  <tr>
    <td width="25%"><img src="docs/screenshots/20-mobile-home-en.jpg" alt="Home page on a phone"></td>
    <td width="25%"><img src="docs/screenshots/21-mobile-style-detail-en.jpg" alt="Style page on a phone"></td>
    <td width="50%"><b>Mobile</b>: home and style page on a phone.</td>
  </tr>
</table>

<details>
<summary>More screenshots</summary>

| | |
|---|---|
| ![Style catalog](docs/screenshots/04-styles-catalog-en.jpg) | ![Another style page](docs/screenshots/06-style-detail-african-royal-en.jpg) |
| Style catalog, filterable by category | Another style page |
| ![Photo upload](docs/screenshots/40-create-upload.jpg) | ![Face detection after upload](docs/screenshots/41-create-face-detection.jpg) |
| Upload (Spanish UI) | Face detection, in the browser, before anything is sent (Spanish UI) |
| ![Professional photos landing](docs/screenshots/08-landing-professional-en.jpg) | ![Valentine's Day landing](docs/screenshots/10-landing-valentines-en.jpg) |
| Professional photos landing | Valentine's Day landing |
| ![Home page in Spanish](docs/screenshots/01-home-hero.jpg) | ![Home page in French](docs/screenshots/13-home-fr.jpg) |
| Spanish (the default language) | French |
| ![My downloads](docs/screenshots/14-my-downloads-en.jpg) | ![404 page](docs/screenshots/42-error-404.jpg) |
| Recovering past portraits by email | 404 page (error pages only came in Spanish) |
| ![Closing page](docs/screenshots/03-closing-page-en.jpg) | ![Create page answering 410 in closed mode](docs/screenshots/15-create-closed-410.jpg) |
| Closed mode: how the site said goodbye | `/create` answering 410 in closed mode (Spanish only) |
| ![Admin style list](docs/screenshots/31-admin-styles.jpg) | ![AI provider logs](docs/screenshots/34-admin-provider-logs.jpg) |
| Admin: styles | Admin: one log line per AI call, with its cost |
| ![Admin gallery examples](docs/screenshots/35-admin-gallery-examples.jpg) | ![Admin products](docs/screenshots/36-admin-products.jpg) |
| Admin: gallery examples | Admin: products |
| ![Mobile gallery](docs/screenshots/22-mobile-gallery-en.jpg) | ![Mobile closing page](docs/screenshots/23-mobile-closing-en.jpg) |
| Mobile gallery | Mobile closing page |

Full-page captures: [home](docs/screenshots/02-home-full-en.jpg) and
[family photos landing](docs/screenshots/11-landing-family-full-en.jpg).

</details>

## What's inside worth taking

- **The full funnel**: upload → face detection → moderation → AI job →
  polling → watermarked preview → email capture → Stripe Checkout →
  signed-URL delivery → automatic file purge.
- **Multi-provider AI with fallback.** fal.ai, OpenRouter, Novita and Evolink
  behind one interface, with per-provider cooldowns, a daily budget that
  reorders the chain cheapest-first, and the cost of every call logged.
- **Stripe done properly**: Checkout with Stripe Tax for EU VAT (OSS),
  signature-checked idempotent webhooks, and a reconciliation command every
  five minutes for webhooks that never arrive.
- **Moderation that costs nothing per image**: a Hugging Face NSFW classifier
  runs before any paid generation, with IP bans in Redis backed by the
  database.
- **Five languages with translated slugs** (`/fotos-de-familia`,
  `/en/family-photos`, `/fr/photos-de-famille`…) and correct `hreflang`. Few
  sites get this right.
- **Filament admin** with nine metric widgets, a private image viewer, GDPR
  anonymization, email campaigns with opt-out and a system health page.
- **A written design system** ([docs/design.md](docs/design.md)) applied to ten
  landing pages, each with a different structure.
- **Privacy by default**: self-hosted fonts, no accounts or passwords for
  buyers, signed URLs for every file, and the original photo is never sent
  back to the browser.
- **178 tests** on in-memory SQLite, run in CI on every push.

## Quick start

```bash
git clone https://github.com/dcarrero/genpic.git
cd genpic
cp .env.example .env
docker compose up -d --build        # first run: dependencies, assets, migrations and seed
docker compose exec app php artisan key:generate
```

Open http://localhost:8101. Emails land in Mailpit at http://localhost:8025.

That gets you the whole site with its 73 styles and the gallery. To log into
the admin, generate portraits or try the checkout without Stripe, follow
**[docs/INSTALL.md](docs/INSTALL.md)**, which also covers installing without
Docker, running the tests and the usual problems.

## External services and what they cost

| Service | Used for | Cost |
|---------|----------|------|
| [fal.ai](https://fal.ai) | Main image generation (SeedReam), upscaling | ≈ $0.035 per image |
| [OpenRouter](https://openrouter.ai) | Fallback generation | ≈ $0.04 per image |
| Novita, Evolink | Optional extra fallbacks (off by default) | ≈ $0.03 per image |
| [Hugging Face](https://huggingface.co) | NSFW moderation | The free tier was enough |
| [Stripe](https://stripe.com) | Payments, EU VAT via Stripe Tax | Standard fees plus Stripe Tax |
| S3-compatible storage | Photos and generated images | Pennies at this scale (production used Wasabi) |
| SMTP | Transactional email | Any provider |
| Redis + MariaDB | Queues, cache, data | Your own server |

The per-image figures are the estimates in `config/image_providers.php`, which
feed the daily budget. Check current prices before you rely on them.

## Before you put this in front of the public

- [ ] **Rewrite the legal pages.** The `legal_*` keys in `lang/*/ui.php` hold a
      privacy policy, terms, cookie policy and legal notice written for a
      Spanish company, now with placeholder company details. Treat them as a
      starting point, not legal advice.
- [ ] Set `APP_URL`, `COMPANY_NAME` and `CONTACT_EMAIL`.
- [ ] Set `ADMIN_EMAILS` and `ADMIN_ALLOWED_IPS`. Changing `ADMIN_PATH` only
      hides the panel; the IP allowlist is what protects it.
- [ ] Your own Stripe keys and webhook secret, with Stripe Tax enabled. An
      empty `STRIPE_SECRET` means simulated checkout, which in production
      means free orders.
- [ ] At least one AI provider key with credit, and a sensible
      `DAILY_COST_BUDGET_USD` (it reorders providers, it doesn't stop them).
- [ ] A private bucket. The app never makes objects public.
- [ ] Your prices, in `database/seeders/FormatsAndProductsSeeder.php` and the
      `pricing_price_*` strings in `lang/*/ui.php`.
- [ ] Check the style prompts against your image provider's content policy
      ([docs/ARTISTIC_PROMPTS.md](docs/ARTISTIC_PROMPTS.md)).
- [ ] Leave `CLOSED_MODE=false` (the default).

Production deployment, with atomic releases, cron and Horizon, is covered in
[docs/DEPLOY.md](docs/DEPLOY.md).

## Documentation

Everything is indexed in **[docs/README.md](docs/README.md)**: installation,
configuration, deployment, customizing (styles, languages, AI providers,
prices, legal pages), architecture and code conventions.

Code comments and some background documents are in Spanish: the project was
built for the Spanish market first.

## Status

The service is closed and there is no roadmap. Issues and pull requests are
welcome, but there's no promise of maintenance. See
[CONTRIBUTING.md](CONTRIBUTING.md) and [SECURITY.md](SECURITY.md).

## Author

Built by **David Carrero Fernández-Baillo** ([carrero.es](https://carrero.es)) at
[Color Vivo](https://colorvivo.com).

## License

[MIT](LICENSE) © 2026 David Carrero Fernández-Baillo

The gallery "before" photos come from Unsplash under the Unsplash License
([credits](public/img/gallery/CREDITS.md)). The fonts in `public/fonts/` are
under the SIL Open Font License 1.1.
