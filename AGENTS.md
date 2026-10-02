# MailPoet - Agent Guidelines

MailPoet is a WordPress email marketing plugin (PHP 7.4+, Doctrine ORM, React 18 + TypeScript, pnpm monorepo). The free plugin lives in `mailpoet/`; shared JS packages in `packages/js/`; build tooling in `tools/`.

The premium plugin is a **separate git repository** cloned into `mailpoet-premium/` (gitignored here). It has its own `AGENTS.md`, branches, commits, and PRs. A change spanning both plugins needs a branch and PR in each repo, released together. Run `git` from the directory you mean to commit in.

## Environment and commands

- Use the root `pnpm` scripts (see `package.json`). `pnpm bootstrap` sets up the repo — `pnpm setup` is a pnpm built-in, not ours.
- Dev site runs on wp-env: `pnpm env:start`, WordPress at http://localhost:8888 (`admin` / `password`), Mailpit at http://localhost:8082.
- Migrations and templates need a live WordPress, so run them via `pnpm migrations:*` / `pnpm templates`, not `./do` on the host.
- Run tests via `pnpm test:*`. Unit and JavaScript tests run on the host; integration and acceptance tests run in the separate `tests_env/` Docker stack, and their wrappers pass `--skip-deps`. Running `./do test:integration` or `./do test:acceptance` without `--skip-deps` can wipe `mailpoet/vendor-prefixed/`. See the `running-tests` skill.
- QA: `pnpm qa`, `pnpm qa:phpstan`, `pnpm qa:fix`. For premium changes, also run QA from `mailpoet-premium/`.
- Run `pnpm compile` after JS/CSS changes before testing in the browser.

## Code rules

Paths below are relative to `mailpoet/` (PHP in `lib/`, frontend in `assets/js/src/`).

- PHP must stay compatible with PHP 7.4. Two-space indentation.
- Call WordPress functions through `MailPoet\WP\Functions` (`$this->wp->…`), never directly, so code stays mockable.
- Get services from the DI container (`lib/DI/`); don't instantiate them directly.
- Sanitize and validate input, escape output. Never commit `.env` files, secrets, or API keys.
- New frontend files are TypeScript; prefer named exports.
- Avoid regular expressions when string/array functions will do; if a regex is necessary, document it.
- Avoid `eslint-disable` / `phpcs:ignore`; when unavoidable, add a comment explaining why.
- Never edit `vendor/`, `vendor-prefixed/`, `lib-3rd-party/`, `generated/`, or `assets/dist/` — they are generated.
- Do not add features to the legacy Backbone newsletter editor (`assets/js/src/newsletter-editor/`); build on the block email editor.
- Feature flags live in `FeaturesController` (toggle at `/wp-admin/admin.php?page=mailpoet-experimental`).
- For queries over subscriber, sending, or stats tables, see the `sql-performance` skill.

## Verify in the context the feature runs in

WordPress loads differently per request type (front end, wp-admin, REST, AJAX, cron, WP-CLI), and plugins skip work in some of them — WooCommerce 11.1+ does not register its blocks on cron or AJAX requests. Emails are rendered and sent from cron; checkout hooks run in front-end or Store API requests.

Verify through the real flow when you can, letting WP-Cron or Action Scheduler process the work through a web request. If you use WP-CLI to save time, match the real context (e.g. `wp --exec='define("DOING_CRON", true);' eval-file ...` for cron code) and report which context you tested. "Works in the editor preview" or "works in WP-CLI" is a partial check, not proof.

## Backward compatibility

A change to an externally exposed surface must state its BC impact in the PR description. That surface is:

- the public API `MailPoet\API\MP\v1\API` (`mailpoet/lib/API/MP/`) — not the internal JSON API in `mailpoet/lib/API/JSON/`;
- `mailpoet_*` actions and filters — renaming, dropping, or changing or reordering their arguments is a break; retire them with `do_action_deprecated()` / `apply_filters_deprecated()`;
- REST routes under `MailPoet\API\REST\`, including request/response shapes and auth expectations;
- classes documented as extension points, and the `window.MailPoet` JS object.

Internal `public` classes (e.g. `MailPoet\Services\Bridge`) are not third-party contracts, but Premium calls some of them, so keep such changes in lockstep with Premium.

- Prefer the additive path: a new optional method, an appended hook argument, a new symbol plus deprecation.
- Never add or remove a required method on an interface outside code can implement.
- Deprecate, don't rename or remove: keep the old symbol working next to the new one.
- Don't implement or type-hint WooCommerce `Internal\` classes; if unavoidable, guard with `class_exists()` / `interface_exists()` / `method_exists()`. (A required method added to core's internal `FeedInterface` once fataled older Stripe Gateway versions.)
- Guard reads of globals and `WC()` state that may not exist in cron, CLI, or REST requests.
- Say whether site-state changes work on multisite. Derive paths and URLs (`plugins_url()`, `wp_upload_dir()`), never concatenate them.
- If you cannot establish the impact, stop and flag it for review.

## Dependencies

- Ask before adding new Composer or npm dependencies — PHP packages may need prefixer configuration.
- All pnpm `overrides` live in `pnpm-workspace.yaml`. Never add a `pnpm` field to the root `package.json`: pnpm then silently ignores the workspace overrides.
- For `pnpm audit` fixes, prefer a lockfile bump (`pnpm -r update <pkg>`); add an override only when a parent's range excludes the patched version. Removing an override does not move a locked version that still satisfies its parents; pair the removal with the update command.
- `tools/mcp-server` has its own lockfile and overrides. Run pnpm there with `--ignore-workspace`, or it operates on the root workspace.

## Git, changelog, PRs

- Never commit to `trunk`. Create branches with the `starting-branch` skill.
- Commit subject: imperative, max 50 characters, no trailing period. Body lines max 72 characters. Include the Linear issue ID.
- User-facing changes need a changelog entry (`pnpm changelog:add`); see the `writing-changelog` skill.
- Create PRs as drafts with the `creating-pull-requests` skill, following `.github/pull_request_template.md`. Never run `gh pr create` directly.

## Available Skills

Skills live in `.ai/skills/` (symlinked as `.claude/skills/`): `creating-pull-requests`, `starting-branch`, `mailpoet-dev-cycle`, `reviewing-code`, `writing-tests`, `running-tests`, `debugging-failed-tests`, `writing-changelog`, `mailpoet-beta-compat-test`, `sql-collation-safety`, `sql-performance`.

`tools/mcp-server/` is an experimental, dev-only MCP server exposing local MailPoet tooling to agents; see its README.
