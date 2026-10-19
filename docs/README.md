# Documentation

Start with [INSTALL.md](INSTALL.md) to run the project, then
[ARCHITECTURE.md](ARCHITECTURE.md) to find your way around the code.

## Running it

- [INSTALL.md](INSTALL.md): local install with Docker or without it, admin
  user, AI keys, simulated payments, tests and common problems.
  In Spanish: [INSTALL-ES.md](INSTALL-ES.md).
- [CONFIGURATION.md](CONFIGURATION.md): every `.env` variable, grouped, with
  its default, and what works without paid services.
- [DEPLOY.md](DEPLOY.md): production on a VPS with atomic releases, cron,
  Horizon, the Stripe webhook and a pre-launch checklist.

## Changing it

- [CUSTOMIZING.md](CUSTOMIZING.md): adding a style, a language or an AI
  provider, changing products and prices, rewriting the legal pages.
- [ARCHITECTURE.md](ARCHITECTURE.md): the main flow, data model, AI provider
  chain, moderation, image delivery, languages and queues.
- [CONVENTIONS.md](CONVENTIONS.md): code layout, patterns, naming and what to
  avoid.

## Background (Spanish)

Written while the product was being built. Useful for the reasoning, but
parts of them describe plans that changed later; the code is the reference.

- [design.md](design.md): the design system and the ten landing page
  structures.
- [ARTISTIC_PROMPTS.md](ARTISTIC_PROMPTS.md): how the style prompts were
  written, legal limits and provider parameters.
- [DB_SCHEMA.md](DB_SCHEMA.md): the original database design.
- [AUDIT_2026_03_26.md](AUDIT_2026_03_26.md): a full code audit and its
  findings.
- [../plans/](../plans/): improvement plans that were carried out, with the
  findings that were discarded and why.
- [../CHANGELOG.md](../CHANGELOG.md): every release, with the reasoning
  behind each fix.

## Screenshots

[screenshots/](screenshots/) holds the site as it ran in production in 2026:
public pages (01–15), mobile (20–23), the admin panel (30–38, with personal
data blurred) and the upload flow (40–42). Files ending in `-en` are the English
versions of the public pages.

## Author

Built by **David Carrero Fernández-Baillo** ([carrero.es](https://carrero.es)) at
[Color Vivo](https://colorvivo.com).

## License

[MIT](../LICENSE) © 2026 David Carrero Fernández-Baillo ([carrero.es](https://carrero.es))
and Color Vivo Internet S.L. ([colorvivo.com](https://colorvivo.com))
