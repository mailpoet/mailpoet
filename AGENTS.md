# MailPoet - Agent Guidelines

## Project Overview

MailPoet is a WordPress email marketing plugin that lets users create, send, and manage newsletters and automated emails from the WordPress dashboard. It integrates deeply with WordPress and WooCommerce.

This is a **monorepo** containing:

- `mailpoet/` -- The free plugin (main codebase)
- `mailpoet-premium/` -- The premium plugin (extends the free version)

**Tech Stack:** PHP 7.4+, WordPress, Doctrine ORM, React 18, TypeScript, SCSS, Webpack, `@wordpress/env` (dev), Docker + Codeception (tests), Mailpit (SMTP catcher), pnpm, Action Scheduler

## Directory Structure

```text
/ (repo root)
├── mailpoet/                    # Free plugin (primary development area)
│   ├── lib/                     # PHP backend (PSR-4, namespace: MailPoet\)
│   ├── lib-3rd-party/           # Vendored third-party libraries (DO NOT EDIT)
│   ├── assets/js/src/           # React/TypeScript frontend
│   ├── assets/css/src/          # SCSS stylesheets
│   ├── tests/                   # Unit, integration, acceptance tests
│   ├── views/                   # Twig templates
│   ├── generated/               # Auto-generated files (DO NOT EDIT)
│   ├── vendor-prefixed/         # Prefixed third-party deps (DO NOT EDIT)
│   ├── RoboFile.php             # Plugin-level task runner
│   └── do                       # Plugin-level CLI script
├── mailpoet-premium/            # Premium plugin
├── packages/js/                 # Shared JS packages (pnpm workspaces)
│   ├── components/              # @mailpoet/components
│   └── eslint-config/           # @mailpoet/eslint-config
├── doc/                         # API documentation and usage examples
├── scripts/                     # Dev helper scripts (setup, SMTP catcher, override generator)
├── tools/                       # Build tooling (Webpack config) + experimental MCP server
│   └── mcp-server/              # Dev-only MCP server exposing MailPoet to AI agents
├── tests_env/                   # Test environment (Docker + Codeception + Selenium)
├── templates/                   # Email templates
├── .wp-env.json                 # wp-env dev environment config
├── .wp-env.override.json.sample # Template for local wp-env overrides
└── .wp-env/                     # wp-env helpers: mu-plugins/ (SMTP router + dev companion), scripts/
```

### Key PHP Namespaces (`mailpoet/lib/`)

| Namespace      | Purpose                                                       |
| -------------- | ------------------------------------------------------------- |
| `API\JSON\`    | Internal JSON API endpoints (v1)                              |
| `API\REST\`    | WordPress REST API endpoints                                  |
| `API\MP\`      | Public developer API (v1)                                     |
| `Automation\`  | Marketing automation engine (triggers, actions, workflows)    |
| `Config\`      | Plugin lifecycle: `Initializer`, `Activator`, `Hooks`, `Menu` |
| `Cron\`        | Background workers, Action Scheduler integration              |
| `DI\`          | Dependency injection container configuration                  |
| `Doctrine\`    | ORM layer, WPDB connection, entity management                 |
| `EmailEditor\` | Block-based email editor integration                          |
| `Entities\`    | Doctrine ORM entities (database table mappings)               |
| `Form\`        | Subscription form handling and rendering                      |
| `Mailer\`      | Email sending (SMTP, Amazon SES, SendGrid, MailPoet service)  |
| `Newsletter\`  | Newsletter management, rendering, scheduling                  |
| `Segments\`    | Subscriber segmentation and dynamic segments                  |
| `Subscribers\` | Subscriber CRUD and management                                |
| `WooCommerce\` | WooCommerce integration                                       |
| `WP\`          | WordPress function wrappers for testability                   |

### Key Frontend Modules (`mailpoet/assets/js/src/`)

| Directory                            | Purpose                                                 |
| ------------------------------------ | ------------------------------------------------------- |
| `automation/`                        | Marketing automation UI (editor, listing, analytics)    |
| `form-editor/`                       | Subscription form builder (Gutenberg-based)             |
| `settings/`                          | Plugin settings pages                                   |
| `newsletters/`                       | Newsletter management UI                                |
| `common/`                            | Shared UI component library                             |
| `newsletter-editor/`                 | Legacy newsletter editor (Backbone.js -- do not extend) |
| `mailpoet-email-editor-integration/` | New block email editor integration                      |

## Development Environment

### Initial Setup

```bash
pnpm bootstrap                   # Install deps, download WC plugins, generate override, compile
# Add secrets to .env files in mailpoet/ and mailpoet-premium/
pnpm env:start                   # Start wp-env + Mailpit SMTP catcher
```

Open:

- **WordPress**: http://localhost:8888 (admin: `admin` / `password`)
- **phpMyAdmin**: http://localhost:8081
- **Mailpit** (captures outgoing mail): http://localhost:8082

Required tools: Docker Desktop, PHP (per `composer.json`), Node.js (per `.nvmrc`), pnpm (via Corepack), [GitHub CLI (`gh`)](https://cli.github.com/).

**GitHub CLI (`gh`) is required** for downloading private WooCommerce test plugins (Subscriptions, AutomateWoo, Memberships). Authenticate with `gh auth login` — no personal access token needed. In CI, the `GH_TOKEN` env var is used instead.

### Root-Level Commands

From the repo root:

```bash
pnpm env:start                       # Start wp-env + Mailpit
pnpm env:stop                        # Stop (state preserved)
pnpm env:destroy                     # Stop + delete all wp-env data
pnpm env:restart                     # Destroy + start (fresh DB)
pnpm env:debug                       # Start with Xdebug enabled (port 9003)
pnpm env:logs                        # Tail container logs
pnpm shell                           # Bash into the wp-env cli container
pnpm shell:test                      # Bash into the tests_env wordpress container
pnpm wp <cmd>                        # wp-cli in the wp-env container
pnpm bootstrap                       # Re-run setup (idempotent)
```

Tests route through `tests_env/` (a separate Codeception stack, untouched by wp-env):

```bash
pnpm test:unit [--file=...]
pnpm test:integration [--file=...]
pnpm test:acceptance [--file=...]
pnpm test:javascript
pnpm test:unit:premium               # Premium variants
pnpm test:integration:premium
pnpm test:acceptance:premium
pnpm test:install-deps               # Fresh composer install before testing
```

Migrations / templates / wp-cli shell into the wp-env container (need a live WP runtime):

```bash
pnpm migrations:new <db|app>
pnpm migrations:status
pnpm templates
```

### Plugin-Level Commands

Use the `pnpm` scripts from the repo root by default. The same common scripts are also exposed inside `mailpoet/` and `mailpoet-premium/`. These wrappers call the plugin-level Robo tasks where appropriate.

**Build:**

```bash
pnpm compile                         # Compile JS + CSS
pnpm compile:js                      # Compile JavaScript only
pnpm compile:css                     # Compile SCSS only
```

**Quality Assurance (runs on host):**

```bash
pnpm qa                              # Run all PHP + frontend QA checks
pnpm qa:php                          # PHP lint + CodeSniffer
pnpm qa:phpstan                      # PHPStan static analysis
pnpm qa:js                           # ESLint + TypeScript check
pnpm qa:css                          # Stylelint for SCSS
pnpm qa:prettier                     # Check Prettier formatting
pnpm qa:fix                          # Auto-fix Prettier formatting
```

For Robo-only helpers without a `pnpm` wrapper, run `./do` from the relevant plugin directory, for example `cd mailpoet && ./do qa:fix-file <path>`.

**Testing:** use the `pnpm test:*` root aliases, which route into `tests_env/` and default to `--skip-deps`.

**Other:**

```bash
pnpm changelog:add --type=<type> --description="<description>"
```

Migrations and templates require a running WordPress and must be invoked via `pnpm migrations:*` / `pnpm templates` (routes through the wp-env container). Running `./do migrations:*` directly from `mailpoet/` fails — no WordPress on the host.

## Code Conventions

### PHP

- Two spaces indentation
- `CamelCase` for classes, `camelCase` for methods and variables/properties
- Composition over inheritance
- Guard clauses over nested conditionals
- Use as few comments as possible — convey your intent through clear coding instead.
- Import classes with `use` statements at the top of the file
- MUST be compatible with PHP 7.4 and newer
- Cover code with tests

### JavaScript / TypeScript

- Follow the [Airbnb JavaScript style guide](https://github.com/airbnb/javascript)
- Prefer named exports over default exports
- MUST default to TypeScript for new files
- Formatting is handled by Prettier (`pnpm qa:fix`)

### SCSS

- `kebab-case` for file names
- Component files prefixed with underscore (`_new-component.scss`)

### Disabling Lint Rules

- Avoid `eslint-disable`. When unavoidable, add a comment explaining why:
  `/* eslint-disable no-new -- this class has a side-effect in the constructor and it's a library's. */`
- For PHP, do the same with `phpcs:ignore`. Exception: `// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps` does not require an explanation.
- Avoid regular expressions when built-in string/array methods can achieve the same result. If regex is necessary, document the pattern extensively.

## Testing

Tests use **Codeception** and run inside the `tests_env/` docker-compose stack (Codeception + Selenium + test MySQL + MailHog). This stack is separate from wp-env and deliberately unchanged by the wp-env migration.

| Type        | File Pattern | Location             | Command                               |
| ----------- | ------------ | -------------------- | ------------------------------------- |
| Unit        | `*Test.php`  | `tests/unit/`        | `pnpm test:unit --file=<path>`        |
| Integration | `*Test.php`  | `tests/integration/` | `pnpm test:integration --file=<path>` |
| Acceptance  | `*Cest.php`  | `tests/acceptance/`  | `pnpm test:acceptance --file=<path>`  |
| JavaScript  | `*.spec.ts`  | `tests/javascript/`  | `pnpm test:javascript`                |

All `pnpm test:*` scripts default to `--skip-deps` (matches the standard dev workflow). Use `pnpm test:install-deps` when deps actually need refreshing.

**Running tests from the repo root:**

```bash
pnpm test:integration --file=tests/integration/WP/EmojiTest.php
pnpm test:acceptance --file=tests/acceptance/Misc/MailpoetMenuCest.php
```

**Running premium tests** — use the premium aliases:

```bash
pnpm test:unit:premium --file=tests/unit/Config/EnvTest.php
pnpm test:integration:premium
pnpm test:acceptance:premium
```

Or shell in and run the plugin-level `./do` directly:

```bash
pnpm shell:test
cd /wp-core/wp-content/plugins/mailpoet-premium
./do test:unit --file=tests/unit/Config/EnvTest.php
```

When writing tests:

- Unit tests should be fast and isolated with no database or WordPress dependency
- Integration tests can use the WordPress database and APIs
- Acceptance tests use browser automation (Selenium/Codeception)
- Place test `DataFactories` in `tests/DataFactories/` for reusable test data builders

## Architecture & Key Patterns

### Doctrine ORM

The plugin uses Doctrine ORM for database management:

- **Entities** in `lib/Entities/` map to database tables (e.g., `SubscriberEntity`, `NewsletterEntity`)
- **Repositories** follow the `*Repository` naming convention (e.g., `SubscribersRepository`)
- **Migrations** in `lib/Migrations/` handle schema changes. Create new ones with `pnpm migrations:new <db|app>` (routes through the wp-env container since a WordPress runtime is required)
- Entity metadata and proxies are cached in `generated/`

### Dependency Injection

PSR-11 container configured in `lib/DI/`:

- `ContainerConfigurator` defines service wiring
- Services are auto-wired based on constructor type hints
- Access services via the container, never instantiate directly

### WordPress Functions Wrapper

**CRITICAL:** `lib/WP/Functions.php` wraps WordPress functions for testability. MUST use `$this->wp->functionName()` (or the injected `WPFunctions` service) instead of calling WordPress functions like `get_option()`, `add_action()`, etc. directly. This enables mocking in tests.

### Vendor Prefixing

Third-party PHP dependencies are prefixed with `MailPoetVendor\` namespace and stored in `vendor-prefixed/`. This prevents conflicts with other plugins that may include the same libraries. NEVER edit these files -- they are generated by the prefixer build process.

### Action Scheduler

Background job processing uses WooCommerce's Action Scheduler library. Cron workers live in `lib/Cron/Workers/`. The `lib/Cron/Daemon` manages scheduling.

### Feature Flags

New features can be gated behind feature flags:

- Managed by `FeaturesController` class
- Toggle flags at `/admin.php?page=mailpoet-experimental`
- Add new flags in `FeaturesController`

### Frontend Architecture

- **Modern admin pages:** React 18 + TypeScript
- **Subscription form builder:** Built on the Gutenberg block editor
- **Legacy newsletter editor:** Backbone.js + Marionette (in `newsletter-editor/`). This is legacy code being replaced by the block-based email editor. Do not add new features here.
- **Block email editor:** New editor integration using `@woocommerce/email-editor` (in `mailpoet-email-editor-integration/`)
- **Webpack** bundles JS with multiple entry points. Run `pnpm compile:js` after changes.

### Multi-API Layer

- `lib/API/JSON/v1/` -- Internal JSON API used by the React admin UI
- `lib/API/REST/` -- WordPress REST API endpoints
- `lib/API/MP/v1/` -- Public API for third-party developers

## Git Workflow & Commits

- MUST NOT commit directly to `trunk`
- Create short-lived feature branches
- Include the Linear issue ID in commit messages
- Run `pnpm qa` and `pnpm qa:fix` before pushing

### Commit Message Format

- Subject line: imperative mood, start with a verb, no trailing period, max 50 characters
- Blank line between subject and body
- Body lines max 72 characters
- Explain what caused the problem and consequences when relevant
- Explain how the changes achieve the goal only if not obvious

```
Add subscriber import validation for CSV files

The previous implementation silently skipped malformed rows,
causing confusion when subscriber counts didn't match.

MAILPOET-1234
```

### Changelog

User-facing changes MUST have a changelog entry. Use:

```bash
pnpm changelog:add --type=Fixed --description="Describe what was fixed"
```

Valid types: `Added`, `Improved`, `Fixed`, `Changed`, `Updated`, `Removed`.
Do not end changelog descriptions with punctuation; release compilation adds semicolons between entries and a period after the final entry.
Write descriptions so they start with a capital letter and read naturally after the generated type prefix. Avoid repeating the type verb, for example use `Generated WooCommerce coupon block support for regular newsletters and automation emails` instead of `Added generated WooCommerce coupon block support for regular newsletters and automation emails`.

### Pull Requests

- Create PRs as **drafts** following `.github/pull_request_template.md`
- Wait for review from another developer
- See `.claude/skills/creating-pull-requests/SKILL.md` for the full PR workflow

## Common Pitfalls

- **NEVER** modify WordPress core files shipped by wp-env. Its WordPress install is managed.
- **NEVER** edit files in `vendor/`, `vendor-prefixed/`, `lib-3rd-party/`, or `generated/`. These are managed by Composer, the prefixer, and build tools respectively.
- **NEVER** edit compiled assets in `assets/dist/`. Run `pnpm compile` to regenerate them.
- **MUST** run `pnpm compile` (or `compile:js` / `compile:css`) after making JS/CSS/SCSS changes before testing in the browser.
- **MUST** use `pnpm test:*` (which default to `--skip-deps`) during development. Running `./do test:integration` directly from `mailpoet/` without `--skip-deps` re-triggers the prefixer inside the container and can wipe `mailpoet/vendor-prefixed/` on PHP 8.4; recover with `pnpm bootstrap`.
- `pnpm <task>` and `./do <task>` are related but not identical: pnpm scripts orchestrate the environment (route to the right container, pass defaults). `mailpoet/do` runs host-side Robo tasks directly.
- `pnpm setup` is a pnpm built-in (configures pnpm itself). Use `pnpm bootstrap` to run our setup script.
- The legacy Backbone.js newsletter editor (`newsletter-editor/`) is being replaced. Do not extend it with new features — build on the block email editor instead.
- When adding PHP dependencies, be aware of vendor prefixing. New dependencies may need prefixer configuration.
- Integration tests run in `tests_env/` (a separate compose stack). Shell in with `pnpm shell:test` to debug.

## Boundaries

### Always Do

- Run QA checks (`pnpm qa`, `pnpm qa:fix`) before committing
- Cover code with unit or integration tests
- Use the `WP\Functions` wrapper instead of calling WordPress functions directly
- Sanitize and validate inputs, escape outputs
- Create changelog entries for user-facing changes
- Ensure backwards compatibility
- Use guard clauses
- Consider query performance at scale -- prefer sargable, indexed queries and pagination over large subscriber/sending/stats tables; see the `sql-performance` skill

### Never Do

- Commit secrets, `.env` files, or API keys
- Modify WordPress core files, `vendor/`, `vendor-prefixed/`, `lib-3rd-party/`, or `generated/` files
- Use `extract()`, `eval()`, or `create_function()`
- Hardcode URLs -- use `home_url()`, `plugin_dir_url()`, `plugin_dir_path()`
- Commit directly to `trunk`

### Ask First

- Database schema changes or new migrations
- Adding new Composer or npm dependencies
- Changes to `.wp-env.json`, `tests_env/`, or CI configuration
- Modifying the DI container configuration
- Changes to the public API (`lib/API/MP/`)

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. A location that looks internal is not a guarantee that a symbol is safe to change: third-party code — other plugins, themes, or custom site code, plus MailPoet Premium — implements and consumes some of these contracts in practice.

Treat a symbol as **externally exposed** when it is implemented or consumed outside this plugin, even if it looks internal. For MailPoet that includes:

- **Public developer API** — `MailPoet\API\MP\v1\API` under `lib/API/MP/`, reached via `\MailPoet\API::MP('v1')`. This is the documented contract third parties build on (subscribe/unsubscribe, subscribers, lists, tags, custom fields); its method names, parameters, and return shapes must stay stable. The internal JSON API in `lib/API/JSON/` serves the React admin only and is not this contract.
- **Custom hooks** — the actions and filters MailPoet fires or registers (the `mailpoet_` prefix, e.g. `mailpoet_link_clicked`), defined largely in `lib/Config/Hooks.php` and `lib/Config/HooksWooCommerce.php`. Renaming a hook, changing its arguments, or dropping it breaks whatever is hooked into it.
- **WordPress REST API** — the `MailPoet\API\REST\` routes, their request/response shapes, and their auth expectations.
- **Public PHP** — any `public` class, method, or function another plugin (including MailPoet Premium) or theme can autoload and call.
- **Front-end globals** — the `window.MailPoet` JS object and any properties page scripts may read.

When in doubt, assume it is exposed and state the BC impact.

**Adding a method to an interface that external code can implement must be flagged explicitly.** It is a backward-incompatible change: existing implementers fatal on load because they no longer satisfy the contract. **Removing a required method from an interface is likewise breaking.** Prefer a non-breaking alternative — add the method to the concrete class rather than the interface, introduce a separate new interface, or supply a default implementation via an abstract base class.

**Deprecate, don't rename.** For existing public symbols (classes, interfaces, methods, constants, hooks), never rename or remove them in place. Mark the old symbol `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window so external consumers have time to migrate.

> Why this matters: a signature change to a shared contract can take down live sites. WooCommerce 10.9.0 was reverted on WP Cloud after a PR added a required `get_entry_count(): int` method to `FeedInterface`, fataling older WooCommerce Stripe Gateway versions that implemented it. The same failure mode applies to any published WooCommerce extension.

## Available Skills

Skills are progressively-revealed instructions loaded on demand.

### `.claude/skills/`

- **`creating-pull-requests`** -- MUST use when creating PRs. Enforces draft PR creation and template compliance. Never run `gh pr create` directly.

### `.ai/skills/`

- `mailpoet-dev-cycle` -- Linting and code quality workflows. Use when fixing code style or following the development workflow.
- `code-quality.md` -- ESLint, Stylelint, Prettier commands and conventions
- `php-coding-standards.md` -- PHP lint, PHPCS, PHPStan commands, ruleset details, naming conventions
- **`starting-branch`** -- MUST use when creating any new branch. Handles branch naming, Linear lookup, and branch creation. Never run `git switch -c` or `git checkout -b` directly.
- `reviewing-code.md` -- Reviewing pull requests or local code changes. Use when asked to review a PR, review code, test changes, verify implementation quality, or do a code review.
- `writing-changelog` -- Use when adding a changelog entry for user-facing changes. Guides through analyzing branch changes, picking the right type, and writing a user-friendly description.

## Experimental: MCP Server for AI Agents

`tools/mcp-server/` hosts an experimental [MCP](https://modelcontextprotocol.io) server that exposes MailPoet local-dev tooling to AI agents (Claude Code, etc.). Not shipped with the plugin, not part of CI, entirely local-dev only.

It gives an agent structured access to things that would otherwise require parsing noisy Robo / wp-cli / container output: environment status, feature flags, migrations, Action Scheduler, subscribers + segments, captured Mailpit emails, unit/integration test runs, QA (phpstan/phpcs/eslint/stylelint/prettier/tsc) with structured violations, and the WP debug log.

Architecture: TS MCP server on the host ↔ PHP companion mu-plugin inside wp-env over loopback HTTP with a shared secret. The companion uses MailPoet's own DI container and Doctrine repositories, so results reflect real entity state. The mu-plugin no-ops unless `WP_DEBUG` is on and the secret file exists — it cannot accidentally run in production.

Every tool call is appended to `.wp-env/mcp-usage.jsonl` (tool name, duration, input keys only — no values) so usage can be analysed later to decide what to expand.

See `tools/mcp-server/README.md` for setup, tool list, configuration, and how to add new tools.

## Additional Resources

- Main README: `README.md`
- Free plugin README: `mailpoet/README.md`
- Premium plugin README: `mailpoet-premium/README.md`
- Contributing guide: `CONTRIBUTING.md`
- Cursor rules: `.cursor/rules/` (review when working on the project)
- MCP server (experimental): `tools/mcp-server/README.md`
