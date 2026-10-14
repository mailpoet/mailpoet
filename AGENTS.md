# MailPoet – Agent Guidelines

MailPoet is a WordPress email marketing plugin (PHP 7.4+, Doctrine ORM, React 18 + TypeScript, pnpm monorepo). The free plugin lives in `mailpoet/`, shared JS packages in `packages/js/`, build tooling in `tools/`.

The premium plugin is a **separate git repository** cloned into `mailpoet-premium/` (gitignored here). It has its own `AGENTS.md`, branches, commits and PRs. A change spanning both plugins needs a branch and PR in each repo, released together. Run `git` from the directory you mean to commit in.

## Commands

| Task                                    | Command                                                         |
| --------------------------------------- | --------------------------------------------------------------- |
| Start / stop dev site                   | `pnpm env:start` / `pnpm env:stop`                              |
| All lint checks                         | `pnpm lint` (`lint:php`, `lint:phpstan`, `lint:js`, `lint:css`) |
| Format                                  | `pnpm format`                                                   |
| Everything CI checks without a database | `pnpm qa`                                                       |
| Tests                                   | `pnpm test:php`, `pnpm test:js`, `pnpm test:e2e`                |

- Use the Node version in `.nvmrc` (`nvm use`).
- `pnpm bootstrap` sets up the repo. `pnpm setup` is a pnpm built-in, not ours.
- The dev site runs on wp-env: WordPress at http://localhost:8888 (`admin` / `password`), Mailpit at http://localhost:8082.
- Run `pnpm compile` after JS/CSS changes before testing in the browser.
- Migrations and templates need a live WordPress, so run them with `pnpm migrations:*` / `pnpm templates`, not `./do` on the host.
- `pnpm qa` runs `lint` (with PHPStan, so it is slow), the Prettier check and the JS tests. CI also runs the PHP test suites, PHP 7.4 lint and Semgrep.
- `test:php` (unit) and `test:js` run on the host. `test:e2e` (acceptance) and `test:integration` run in the separate `tests_env/` Docker stack. See the `running-tests` skill.
- For premium changes, also run `pnpm qa` from `mailpoet-premium/`.

## Git hooks

On commit, Husky runs lint-staged on staged files. To turn checks off, set these in `mailpoet/.env` (gitignored): `MP_GIT_HOOKS_ENABLE=false` turns off all hooks; `MP_GIT_HOOKS_ESLINT`, `MP_GIT_HOOKS_STYLELINT`, `MP_GIT_HOOKS_PHPLINT`, `MP_GIT_HOOKS_CODE_SNIFFER` and `MP_GIT_HOOKS_PHPSTAN` turn off one check each. Hooks only see staged files, so still run `pnpm qa` before pushing. Do not use `--no-verify`.

`MP_GIT_HOOKS_INSTALL_JS=true` and `MP_GIT_HOOKS_INSTALL_PHP=true` reinstall dependencies after a checkout, merge or rebase that changes a lockfile. Both are off by default.

## Architecture

Paths are relative to `mailpoet/`.

- `lib/` – PHP, namespace `MailPoet\`. `Config/Initializer.php` boots the plugin; services are registered in `DI/ContainerConfigurator.php`.
- `lib/Entities/` – Doctrine entities. Migrations live in `lib/Migrations/Db/` and `lib/Migrations/App/` (`pnpm migrations:new db|app`).
- `lib/Automation/` – the automation engine. `lib/EmailEditor/` – the block email editor integration.
- `assets/js/src/` – admin and public frontend, compiled to `assets/dist/`. `views/` – Twig templates.
- `tests/` – `unit/`, `integration/`, `acceptance/`, `javascript/`, `performance/`; data factories in `tests/DataFactories/`.
- `tools/mcp-server/` (repo root) is an experimental, dev-only MCP server that exposes local MailPoet tooling to agents; see its README.

## Code rules

Paths are relative to `mailpoet/`.

- PHP must stay compatible with PHP 7.4. Two-space indentation.
- Call WordPress functions through `MailPoet\WP\Functions` (`$this->wp->…`), never directly, so code stays mockable.
- Get services from the DI container (`lib/DI/`); don’t instantiate them directly.
- New frontend files are TypeScript; prefer named exports.
- Avoid regular expressions when string/array functions will do; if a regex is necessary, document it.
- Avoid `eslint-disable` / `phpcs:ignore`; when unavoidable, add a comment explaining why.
- Do not add features to the legacy Backbone newsletter editor (`assets/js/src/newsletter-editor/`); build on the block email editor.
- Feature flags live in `FeaturesController` (toggle at `/wp-admin/admin.php?page=mailpoet-experimental`).
- For queries over subscriber, sending or stats tables, see the `sql-performance` skill.

## Backward compatibility

A change to an externally exposed surface must state its BC impact in the PR description. If you cannot establish the impact, stop and flag it.

That surface is:

- the public API `MailPoet\API\MP\v1\API` (`mailpoet/lib/API/MP/`) – not the internal JSON API in `mailpoet/lib/API/JSON/`;
- `mailpoet_*` actions and filters – renaming, dropping, or changing or reordering their arguments is a break; retire them with `do_action_deprecated()` / `apply_filters_deprecated()`;
- REST routes under `MailPoet\API\REST\`, including request/response shapes and auth expectations;
- classes documented as extension points, and the `window.MailPoet` JS object.

Internal `public` classes (e.g. `MailPoet\Services\Bridge`) are not third-party contracts, but Premium calls some of them, so keep such changes in lockstep with Premium.

- Prefer the additive path: a new optional method, an appended hook argument, a new symbol plus deprecation.
- Never add or remove a required method on an interface outside code can implement.
- Deprecate, don’t rename or remove: keep the old symbol working next to the new one.
- Don’t implement or type-hint WooCommerce `Internal\` classes; if unavoidable, guard with `class_exists()` / `interface_exists()` / `method_exists()`. (A required method added to core’s internal `FeedInterface` once fataled older Stripe Gateway versions.)
- Guard reads of globals and `WC()` state that may not exist in cron, CLI or REST requests.
- Say whether site-state changes work on multisite. Derive paths and URLs (`plugins_url()`, `wp_upload_dir()`), never concatenate them.

## Verify in the context the feature runs in

WordPress loads differently per request type (front end, wp-admin, REST, AJAX, cron, WP-CLI), and plugins skip work in some of them – WooCommerce 11.1+ does not register its blocks on cron or AJAX requests. Emails are rendered and sent from cron; checkout hooks run in front-end or Store API requests.

Verify through the real flow when you can, letting WP-Cron or Action Scheduler process the work through a web request. If you use WP-CLI to save time, match the real context (e.g. `wp --exec='define("DOING_CRON", true);' eval-file ...` for cron code) and report which context you tested. “Works in the editor preview” or “works in WP-CLI” is a partial check, not proof.

## Dependencies

- PHP packages may need prefixer configuration, so ask before adding Composer or npm dependencies.
- All pnpm `overrides` live in `pnpm-workspace.yaml`. Never add a `pnpm` field to the root `package.json`: pnpm then silently ignores the workspace overrides.
- For `pnpm audit` fixes, prefer a lockfile bump (`pnpm -r update <pkg>`); add an override only when a parent’s range excludes the patched version. Removing an override does not move a locked version that still satisfies its parents; pair the removal with the update command.
- `tools/mcp-server` has its own lockfile and overrides. Run pnpm there with `--ignore-workspace`, or it operates on the root workspace.

## Gotchas

- Running `./do test:integration` or `./do test:acceptance` without `--skip-deps` can wipe `mailpoet/vendor-prefixed/`. The `pnpm test:*` wrappers pass it; recover with `pnpm bootstrap`.
- Run Prettier on the host, never through Docker’s `./do run`: Docker can resolve a different Prettier version than CI’s 2.6.2. See the `code-quality` skill.
- `pnpm wp` is `wp-env run cli wp`, which can swallow WP-CLI flags. Use `pnpm exec wp-env run cli -- wp …` when passing flags.

## Git and pull requests

Branch from `trunk`; never commit to it. Start branches with the `starting-branch` skill. Open PRs with the `creating-pull-requests` skill; never run `gh pr create` directly. PRs follow `.github/pull_request_template.md`; write `_N/A_` in sections that don’t apply. User-facing changes need a changelog file: `pnpm changelog:add --type=<type> --description=<description>` (see `writing-changelog`).

- Commit subject: imperative, max 50 characters, no trailing period. Body lines max 72 characters. Include the Linear issue ID.
- Open PRs as drafts.

## Skills

Skills live in `.ai/skills/` (`.claude/skills` links there). Claude Code loads them as skills. Other agents read `.ai/skills/<name>/SKILL.md` when the task matches. After reading one, also apply `~/.ai/skills/<name>-personal/SKILL.md` if it exists; it wins on conflict.

- `starting-branch` – name and create a branch from `trunk` for a Linear issue.
- `creating-pull-requests` – run QA, add the changelog, push and open a draft PR from the template.
- `code-quality` – lint, format and `qa` commands, single-file fixes, hook switches, what CI runs.
- `running-tests` – run a suite, one file or one test, on the host or in Docker.
- `writing-tests` – pick the suite, then place and structure a new test.
- `debugging-failed-tests` – investigate a failing CircleCI job or local run.
- `reviewing-code` – review a PR or local changes against these rules.
- `writing-changelog` – write the changelog file for a user-facing change.
- `beta-compat-test` – test the released plugin against a WordPress or WooCommerce beta/RC.
- `sql-performance` – keep queries fast on large subscriber and stats tables.
- `sql-collation-safety` – avoid collation errors in cross-table text comparisons.

## Boundaries

- **Always:** run `pnpm qa` and the tests for the code you touched before pushing; sanitize input and escape output; follow the PR template; validate input; keep PHP 7.4 compatible; use the `mailpoet` text domain (`mailpoet-premium` in Premium).
- **Ask first:** new Composer or npm dependencies; CI workflow changes; changes to a surface listed under Backward compatibility; changes to `.wp-env.json`, `tests_env/` or the DB schema.
- **Never:** commit to `trunk`; commit `.env`, secrets or API keys; edit generated or vendored files (`vendor/`, `vendor-prefixed/`, `lib-3rd-party/`, `generated/`, `assets/dist/`); skip hooks with `--no-verify`.
