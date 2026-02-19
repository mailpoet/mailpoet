# MailPoet - Agent Guidelines

## Project Overview

MailPoet is a WordPress email marketing plugin that lets users create, send, and manage newsletters and automated emails from the WordPress dashboard. It integrates deeply with WordPress and WooCommerce.

This is a **monorepo** containing:

- `mailpoet/` -- The free plugin (main codebase)
- `mailpoet-premium/` -- The premium plugin (extends the free version)

**Tech Stack:** PHP 7.4+, WordPress, Doctrine ORM, React 18, TypeScript, SCSS, Webpack, Docker, Codeception, pnpm, Action Scheduler

## Directory Structure

```text
/ (repo root)
├── mailpoet/                    # Free plugin (primary development area)
│   ├── lib/                     # PHP backend (PSR-4, namespace: MailPoet\)
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
├── dev/                         # Docker dev configuration
├── tests_env/                   # Test environment (Docker + Codeception)
├── templates/                   # Email templates
├── do                           # Root CLI script (Docker wrapper)
└── docker-compose.yml           # Docker configuration
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
./do setup                       # Pull images, install dependencies
# Add secrets to .env files in mailpoet/ and mailpoet-premium/
./do start                       # Start Docker containers
# Visit http://localhost:8002
```

Additional local tools recommended: PHP (per `composer.json`), Node.js (per `.nvmrc`), pnpm (via Corepack).

### Root-Level Commands

The root `./do` script wraps Docker operations. Run from the repo root:

```bash
./do setup                           # Initial environment setup
./do start                           # Start Docker (docker compose up -d)
./do stop                            # Stop Docker (docker compose stop)
./do ssh [--test] [--premium]        # Shell into plugin directory in container
./do run [--test] <command>          # Run command in wordpress container
./do build [--premium]               # Build plugin .zip
./do templates                       # Generate email template classes
./do [--test] [--premium] <command>  # Run plugin-level ./do command in Docker
```

The `--test` flag targets the `test_wordpress` service. The `--premium` flag targets `mailpoet-premium/`.

### Plugin-Level Commands

Run inside `mailpoet/` (or via root `./do <command>` which forwards to the plugin):

**Build:**

```bash
./do compile:all                     # Compile JS + CSS
./do compile:js                      # Compile JavaScript only
./do compile:css                     # Compile SCSS only
./do install                         # Install PHP + JS dependencies
```

**Quality Assurance:**

```bash
./do qa                              # Run all PHP + frontend QA checks
./do qa:php                          # PHP lint + CodeSniffer
./do qa:phpstan                      # PHPStan static analysis
./do qa:lint-javascript              # ESLint + TypeScript check
./do qa:lint-css                     # Stylelint for SCSS
./do qa:prettier-check               # Check Prettier formatting
./do qa:prettier-write               # Auto-fix Prettier formatting
./do qa:fix-file <path>              # Auto-fix a single file (PHPCS or ESLint)
```

**Testing:**

```bash
./do test:unit --file=tests/unit/Path/To/SomeTest.php
./do test:integration --skip-deps --file=tests/integration/Path/To/SomeTest.php
./do test:acceptance --skip-deps --file=tests/acceptance/Path/To/SomeCest.php
./do test:javascript                 # Mocha JS tests
./do test:newsletter-editor          # Legacy newsletter editor JS tests
```

**Other:**

```bash
./do migrations:new <db|app>         # Create a new database migration
./do changelog:add --type=<type> --description="<description>"
./do changelog:preview               # Preview compiled changelog
```

## Code Conventions

### PHP

- Two spaces indentation
- `CamelCase` for classes, `camelCase` for methods, `snake_case` for variables and properties
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
- Formatting is handled by Prettier (`./do qa:prettier-write`)

### SCSS

- `kebab-case` for file names
- Component files prefixed with underscore (`_new-component.scss`)

### Disabling Lint Rules

- Avoid `eslint-disable`. When unavoidable, add a comment explaining why:
  `/* eslint-disable no-new -- this class has a side-effect in the constructor and it's a library's. */`
- For PHP, do the same with `phpcs:ignore`. Exception: `// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps` does not require an explanation.
- Avoid regular expressions when built-in string/array methods can achieve the same result. If regex is necessary, document the pattern extensively.

## Testing

Tests use **Codeception** and run inside Docker.

| Type        | File Pattern | Location             | Command                                           |
| ----------- | ------------ | -------------------- | ------------------------------------------------- |
| Unit        | `*Test.php`  | `tests/unit/`        | `./do test:unit --file=<path>`                    |
| Integration | `*Test.php`  | `tests/integration/` | `./do test:integration --skip-deps --file=<path>` |
| Acceptance  | `*Cest.php`  | `tests/acceptance/`  | `./do test:acceptance --skip-deps --file=<path>`  |
| JavaScript  | `*.spec.ts`  | `tests/javascript/`  | `./do test:javascript`                            |

**Running tests in Docker from the repo root:**

```bash
./do --test test:integration --skip-deps --file=tests/integration/WP/EmojiTest.php
./do --test test:acceptance --skip-deps --file=tests/acceptance/Misc/MailpoetMenuCest.php
```

**Running premium tests** requires SSHing into the test container:

```bash
./do ssh --test
cd ../mailpoet-premium
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
- **Migrations** in `lib/Migrations/` handle schema changes. Create new ones with `./do migrations:new <db|app>`
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
- **Webpack** bundles JS with multiple entry points. Run `./do compile:js` after changes.

### Multi-API Layer

- `lib/API/JSON/v1/` -- Internal JSON API used by the React admin UI
- `lib/API/REST/` -- WordPress REST API endpoints
- `lib/API/MP/v1/` -- Public API for third-party developers

## Git Workflow & Commits

- MUST NOT commit directly to `trunk`
- Create short-lived feature branches
- Include the Linear issue ID in commit messages
- Run `./do qa` and `./do qa:prettier-write` before pushing

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
./do changelog:add --type=Fixed --description="Describe what was fixed"
```

Valid types: `Added`, `Improved`, `Fixed`, `Changed`, `Updated`, `Removed`.

### Pull Requests

- Create PRs as **drafts** following `.github/pull_request_template.md`
- Wait for review from another developer
- See `.claude/skills/creating-pull-requests/SKILL.md` for the full PR workflow

## Common Pitfalls

- **NEVER** modify WordPress core files in `wordpress/`. This directory is for the local dev environment only.
- **NEVER** edit files in `vendor/`, `vendor-prefixed/`, or `generated/`. These are managed by Composer, the prefixer, and build tools respectively.
- **NEVER** edit compiled assets in `assets/dist/`. Run `./do compile:all` to regenerate them.
- **MUST** run `./do compile:all` (or `compile:js` / `compile:css`) after making JS/CSS/SCSS changes before testing in the browser.
- **MUST** use the `--skip-deps` flag for integration and acceptance tests during development to avoid slow dependency reinstallation.
- The root `./do` runs commands inside Docker containers. The `mailpoet/do` script is the plugin-level Robo task runner. They are different scripts.
- The `--test` flag on root `./do` targets the `test_wordpress` Docker service, which has a separate database and WordPress installation.
- The legacy Backbone.js newsletter editor (`newsletter-editor/`) is being replaced. Do not extend it with new features -- build on the block email editor instead.
- When adding PHP dependencies, be aware of vendor prefixing. New dependencies may need prefixer configuration.
- Integration tests need a running Docker test environment. Use `./do ssh --test` to debug test failures interactively.

## Boundaries

### Always Do

- Run QA checks (`./do qa`, `./do qa:prettier-write`) before committing
- Cover code with unit or integration tests
- Use the `WP\Functions` wrapper instead of calling WordPress functions directly
- Sanitize and validate inputs, escape outputs
- Create changelog entries for user-facing changes
- Ensure backwards compatibility
- Use guard clauses

### Never Do

- Commit secrets, `.env` files, or API keys
- Modify WordPress core files, `vendor/`, `vendor-prefixed/`, or `generated/` files
- Use `extract()`, `eval()`, or `create_function()`
- Hardcode URLs -- use `home_url()`, `plugin_dir_url()`, `plugin_dir_path()`
- Commit directly to `trunk`

### Ask First

- Database schema changes or new migrations
- Adding new Composer or npm dependencies
- Changes to Docker or CI configuration
- Modifying the DI container configuration
- Changes to the public API (`lib/API/MP/`)

## Available Skills

Skills are progressively-revealed instructions loaded on demand. Located in `.claude/skills/`:

- **`creating-pull-requests`** -- MUST use when creating PRs. Enforces draft PR creation and template compliance. Never run `gh pr create` directly.

## Additional Resources

- Main README: `README.md`
- Free plugin README: `mailpoet/README.md`
- Premium plugin README: `mailpoet-premium/README.md`
- Contributing guide: `CONTRIBUTING.md`
- Cursor rules: `.cursor/rules/` (review when working on the project)
