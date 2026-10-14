---
name: code-quality
description: Use when linting, formatting or fixing code style in MailPoet (PHP, JS/TS, CSS/SCSS), or when a QA check fails locally or in CI. Triggers on "run lint", "fix the lint errors", "run QA", "Prettier fails in CI", "PHPStan error", "turn off the commit hook".
---

# Code Quality

Linting, formatting and static analysis for MailPoet. For tests, see `running-tests`.

Run `pnpm <script>` from the repo root; the scripts wrap the plugin-level Robo tasks (`./do`) from the right directory. Use `./do` from `mailpoet/` only for helpers without a `pnpm` script. Default to the free plugin unless you are working on `mailpoet-premium/`.

| Document                                           | Covers                                    |
| -------------------------------------------------- | ----------------------------------------- |
| [frontend-quality.md](frontend-quality.md)         | ESLint, TypeScript, Stylelint, Prettier   |
| [php-coding-standards.md](php-coding-standards.md) | PHP lint, PHPCS rules and naming, PHPStan |

## Commands

```bash
pnpm qa             # lint + Prettier check + JS tests – run before pushing
pnpm lint           # lint:php + lint:phpstan + lint:js + lint:css
pnpm lint:php       # PHP syntax lint + PHPCS
pnpm lint:phpstan   # PHPStan (slow)
pnpm lint:js        # TypeScript check + ESLint
pnpm lint:css       # Stylelint
pnpm qa:prettier    # Prettier check
pnpm format         # Prettier write

# Fix one file: PHPCBF for .php, ESLint --fix for JS/TS (Robo-only)
cd mailpoet && ./do qa:fix-file path/to/file.php
cd mailpoet && ./do qa:fix-file path/to/file.tsx
```

The older `qa:php`, `qa:phpstan`, `qa:js`, `qa:css` and `qa:fix` scripts still exist; `lint:*` and `format` call them.

## Before you commit

1. `pnpm lint` passes.
2. `pnpm format` leaves no changes, so CI’s Prettier check passes.
3. The tests for the code you touched pass (see `running-tests`).

`pnpm qa` covers 1, the Prettier check and the JS tests. Commit auto-formatting with the change, not as a follow-up commit.

## Commit hook

On commit, Husky runs lint-staged twice:

- root `package.json`: Prettier on every staged file;
- `mailpoet/package.json`: `tasks/lint-staged-js.sh` (ESLint; files the ESLint config ignores are skipped), `tasks/lint-staged-css.sh` (Stylelint) and `tasks/lint-staged-php.sh` (PHP lint, PHPCS, PHPStan).

Switches live in `mailpoet/.env`, seeded from `.env.sample`. A check runs only when its switch is `true`:

| Variable                    | Controls                                            |
| --------------------------- | --------------------------------------------------- |
| `MP_GIT_HOOKS_ENABLE`       | All hooks                                           |
| `MP_GIT_HOOKS_ESLINT`       | ESLint on staged `.js`, `.jsx`, `.ts`, `.tsx` files |
| `MP_GIT_HOOKS_STYLELINT`    | Stylelint on staged `.scss`, `.css` files           |
| `MP_GIT_HOOKS_PHPLINT`      | PHP lint on staged `.php` files                     |
| `MP_GIT_HOOKS_CODE_SNIFFER` | PHPCS on staged `.php` files                        |
| `MP_GIT_HOOKS_PHPSTAN`      | PHPStan when `.php` files are staged                |
| `MP_GIT_HOOKS_INSTALL_JS`   | `./do install:js` after a lockfile change (off)     |
| `MP_GIT_HOOKS_INSTALL_PHP`  | `./do install:php` after a lockfile change (off)    |

Hooks see only staged files, so still run `pnpm qa` before pushing. Do not use `--no-verify`.

## What CI runs

CircleCI (`.circleci/config.yml`) calls the Robo tasks from `mailpoet/`:

| Job                 | Command                                       | PHP              |
| ------------------- | --------------------------------------------- | ---------------- |
| `build`             | `./do qa:prettier-check`                      | –                |
| `qa_js`             | `./do qa:frontend-assets` (ESLint, Stylelint) | –                |
| `qa_php`            | `./do qa:php` (lint + PHPCS)                  | 8.3              |
| `qa_php_oldest`     | `./do qa:php`                                 | 7.4              |
| `qa_php_max_wporg`  | `./do qa:php-max-wporg` (lint of the build)   | 8.1 (WP.org max) |
| `static_analysis`   | `./do qa:phpstan`                             | 8.3              |
| `security_analysis` | `./do qa:semgrep`                             | 8.1              |
| `js_tests`          | JS and legacy newsletter editor tests         | –                |

`pnpm qa` does not run Semgrep, the PHP 7.4 lint, the WP.org build lint or the newsletter editor tests.

## Premium

`mailpoet-premium/` has the same `lint`, `format` and `qa` scripts; run them from that directory. Its `qa` has no JS tests. PHPCS uses `premium-ruleset.xml`, so the text domain is `mailpoet-premium`. Its hook switches live in `mailpoet-premium/.env`.

```bash
cd mailpoet-premium && pnpm qa
cd mailpoet-premium && ./do qa:fix-file lib/SomePremiumClass.php
```
