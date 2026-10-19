# Customizing

The usual changes when you take this code for your own project. Read
[ARCHITECTURE.md](ARCHITECTURE.md) first if you haven't.

After changing seeders, translations or routes, run:

```bash
php artisan db:seed --force          # seeders are idempotent (updateOrInsert)
php artisan optimize:clear           # config, route, view and page caches
```

Public pages are cached as full HTML for `CACHE_PUBLIC_PAGES_HOURS`; set it
to `0` while you work.

## Add an artistic style

A style is a row in `artistic_styles`. The admin can edit labels,
descriptions, order and the active flag, but it can't create styles and shows
the prompts read-only, so new styles go in a seeder.

1. **Add an entry** to `database/seeders/ArtisticStylesSeeder.php` (the
   original styles) or `database/seeders/NewStyleCategoriesSeeder.php` (the
   professional, thematic and adventure ones). Copy an existing entry. The
   fields that matter:

   | Field | Notes |
   |-------|-------|
   | `style_key` | Unique, snake_case. Used in URLs (`/style/{style_key}`) and image filenames. |
   | `style_category` | One of `historic`, `artistic`, `cultural`, `modern`, `regional`, `events`, `professional`, `thematic`, `adventure`. In `ArtisticStylesSeeder` the category is assigned by `StyleCategoriesSeeder` instead: add your key to its map. |
   | `label`, `description` | JSON with one entry per locale. `label_es`/`label_en` and `description_es`/`description_en` are legacy columns still filled in. |
   | `base_prompt`, `negative_prompt` | In English. `PromptBuilder` adds the face-preservation rules and the orientation hint on top. |
   | `style_strength` | 0–1. Lower keeps more of the original photo. |
   | `max_faces` | Optional. The professional styles use `1`. |
   | `sort_order`, `active` | |

   [ARTISTIC_PROMPTS.md](ARTISTIC_PROMPTS.md) (Spanish) explains how the
   prompts were written and which words providers tend to refuse.

2. **Seed it**: `php artisan db:seed --force`.

3. **Generate the sample images.** Without them the style still works, but
   its page has no before/after and the catalog card has no picture. The
   command runs the style against a photo of yours and writes
   `public/img/styles/{style_key}.jpg`, `{style_key}_thumb.jpg` and
   `{style_key}_thumb.webp`:

   ```bash
   php artisan styles:generate-previews path/to/photo.jpg --style=your_style_key
   ```

   It needs `FAL_AI_KEY` (the photo is uploaded to fal.ai's CDN) and uses
   your provider chain. The style page shows `public/img/original-woman.jpg`
   as the "before" for professional, thematic and adventure styles and
   `public/img/original-family.jpg` for the rest, so use the matching photo
   if you want the comparison to make sense.

A new **category** also needs a filter button in
`resources/views/styles/index.blade.php` and a `styles_{category}` string in
every `lang/*/ui.php`.

## Add a language

Spanish is the default and has no URL prefix; every other language lives
under `/{code}/`. Slugs are translated, which is why adding a language takes
more than a translation file.

1. **Translations.** Copy `lang/en/` to `lang/{code}/` and translate
   `ui.php` (about 1,050 strings) and `validation.php`. Translate the slug keys
   too: `lp_slug_*` (the ten landing pages) and `closing_slug`. They become
   URLs, so keep them lowercase and hyphenated.
2. **Register it** in `config/genpic.php` under `locale_map` (native name,
   flag, `og:locale` and the Stripe Checkout locale), and add the code to
   `APP_LOCALES` in `.env`.
3. **Content in the database.** Styles, products, gallery examples,
   testimonials and email sequences store their texts as JSON per locale.
   Add your language to the seeders. Missing entries fall back to English,
   then Spanish (`ArtisticStyle::t()`). Testimonials, email sequences and
   campaigns can also be edited per locale in the admin.
4. `php artisan db:seed --force && php artisan optimize:clear`.

Routes, the sitemap, `hreflang` tags and the language switcher all loop over
`APP_LOCALES` and resolve URLs by route name (`locale_route()` in
`app/helpers.php`), so nothing else needs touching. Never build a localized
URL by gluing the prefix onto the current path: the slug changes too.

## Add an AI provider

Providers live in `app/Services/ImageGeneration/Providers/` and implement
`App\Services\ImageGeneration\Contracts\ImageProviderInterface`:

```php
public function getName(): string;                  // key used in config and logs
public function generate(array $params): array;     // see the docblock for both shapes
public function estimateCost(array $params): float; // USD per image
public function isAvailable(): bool;                // enabled and has a key
```

`generate()` receives a URL of the photo, the prompt, negative prompt, style
strength and target size, and returns the binary image plus provider, model,
seed and elapsed time. Throw `App\Exceptions\ContentPolicyException` when the
provider refuses the image on content grounds (that stops the chain) and any
other exception for everything else (that moves on to the next provider).
`NovitaProvider` is the shortest one to copy.

Then:

1. Add its config block (`enabled`, `key`, `base_url`, `model`,
   `timeout_seconds`, `cost_per_image`) to `config/image_providers.php`.
2. Register the class in `ImageGenerationService::$providerMap`.
3. Add its name to `AI_PROVIDER_CHAIN` and its variables to `.env.example`.

Logging, cooldowns, the budget reordering and admin alerts come for free from
`ImageGenerationService`.

## Products and prices

Products and output formats are seeded by
`database/seeders/FormatsAndProductsSeeder.php` and can be edited in the admin
(*Products*). Prices are integers in cents (`price_es_cents`; never floats).
Checkout sends them to Stripe inline, so there is nothing to create in Stripe.

Three things are hard-coded and need changing by hand if they don't suit you:

- The currency is **EUR**, with tax-inclusive prices
  (`PaymentController::createSession`).
- The prices shown on the landing page come from translation strings, not
  from the database: `pricing_price_*` in every `lang/*/ui.php`.
- Output sizes and quality per format are in `config/genpic.php` under
  `formats`.

## Legal pages

The privacy policy, terms, cookie policy and legal notice
(`resources/views/legal/`) are made of the `legal_*` strings in
`lang/*/ui.php`, about 250 per language. They were written for a Spanish
company selling to EU consumers, and the company details are now
placeholders. Rewrite them for your business and jurisdiction, in every
language you enable, before the site goes public. They are a starting point,
not legal advice.

`COMPANY_NAME` and `CONTACT_EMAIL` cover the footer, emails and image
metadata, but not the body of the legal texts.

## Design

Colours, fonts and the rules for each page type are in
[design.md](design.md) (Spanish). The Tailwind palette is in
`tailwind.config.js`; the self-hosted fonts in `public/fonts/` and their
`@font-face` rules in `resources/css/app.css`. Each Blade view starts with a
comment naming its layout family; `DesignSystemStampTest` checks that every
view has one, so copy it when you add views.
