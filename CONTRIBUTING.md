# Contributing

GenPic is no longer a running service. This repository is published so other
people can learn from it or reuse it, and nobody is paid to maintain it.

That said:

- **Bug fixes and small improvements are welcome.** Open a pull request with a
  clear description and a test when it makes sense. `php artisan test` must
  pass; CI runs it on PHP 8.4.
- **Questions and ideas** can go in an issue. Answers may take a while, or
  never come.
- **Big refactors or new features** probably belong in your own fork. Feel
  free: that's what the MIT license is for.

Follow the existing style ([docs/CONVENTIONS.md](docs/CONVENTIONS.md)):
thin controllers, logic in services, user-facing text in `lang/` for all five
languages, prices in integer cents.

Comments can be in English or Spanish.
