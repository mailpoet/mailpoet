---
name: running-tests
description: Use when running MailPoet tests — executing a full suite, running a single file or single test, running in debug/multisite mode, or shelling into the test container. Triggers on phrases like "run the unit tests", "run this test file", "execute the integration suite", "kick off acceptance tests", "rerun the failed tests". Does not cover authoring tests (see writing-tests) or investigating a failed CI run (see debugging-failed-tests).
---

# Running Tests

## Overview

MailPoet has six test suites across two plugins (free `mailpoet/`, premium `mailpoet-premium/`). Unit and JavaScript run on the host; integration, acceptance, and performance run inside the `tests_env/` Docker stack — separate from the wp-env dev stack. A set of `pnpm test:*` wrappers from the monorepo root hides most of that. Start with those; drop down to plugin-level `./do` calls only for the few helpers without a `pnpm` equivalent.

## Most common invocations

```bash
pnpm test:unit --file=tests/unit/<Path>Test.php
pnpm test:integration --file=tests/integration/<Path>Test.php
pnpm test:acceptance --file=tests/acceptance/<Path>Cest.php
pnpm test:integration --file=tests/integration/<Path>Test.php:testMethodName   # one method
pnpm test:integration:premium --file=tests/integration/<Path>Test.php          # premium variant
```

That covers ~90% of typical traffic. The rest of this skill handles the long tail.

## Quick reference

| Suite             | Whole suite                                  | Single file                                                     | Runs in | Plugin    | Typical time on M1                 |
| ----------------- | -------------------------------------------- | --------------------------------------------------------------- | ------- | --------- | ---------------------------------- |
| Unit              | `pnpm test:unit`                             | `pnpm test:unit --file=tests/unit/<Path>Test.php`               | Host    | Free only | ~30s full / sub-second per file    |
| Integration       | `pnpm test:integration`                      | `pnpm test:integration --file=tests/integration/<Path>Test.php` | Docker  | Both      | ~5–10min full / ~30s per file      |
| Acceptance        | `pnpm test:acceptance`                       | `pnpm test:acceptance --file=tests/acceptance/<Path>Cest.php`   | Docker  | Both      | many hours full / ~1–2min per file |
| JavaScript        | `pnpm test:javascript`                       | (no per-file flag — see JavaScript section below)               | Host    | Free only | ~30s full                          |
| Newsletter Editor | `cd mailpoet && ./do test:newsletter-editor` | (Robo-only)                                                     | Host    | Free only | rare                               |
| Performance       | `cd mailpoet && ./do test:performance ...`   | —                                                               | Docker  | Free only | on demand                          |

Times are rough; first run on a cold Docker stack pays an extra 1–2min for container build/pull.

**Premium variants:** replace `test:unit` / `test:integration` / `test:acceptance` with `test:unit:premium` / `test:integration:premium` / `test:acceptance:premium`. Paths are relative to whichever plugin is being tested.

**Single test method** (unit, integration, acceptance): append `:methodName` to the file path.

```bash
pnpm test:integration --file=tests/integration/Logging/LogHandlerTest.php:testItStoresLog
pnpm test:acceptance --file=tests/acceptance/Misc/WordPressSiteEditorCest.php:editWithFSE
```

## Flags

`pnpm` forwards extra args transparently — `pnpm test:integration --file=X --debug` ends up as `cd mailpoet && ./do test:integration --skip-deps --file=X --debug`. Every flag below works through the `pnpm` wrapper.

Source of truth: `mailpoet/RoboFile.php` (search for `public function test*`).

- `--file=<path>[:method]` — all suites. Run a single file, or one method within it.
- `--debug` — unit, integration (incl. woo/base variants). Codeception verbose mode: full stack traces, per-step output. Not on acceptance.
- `--group=<name>` — integration, acceptance (incl. multisite). Filter to tests tagged `@group <name>` (e.g. `woo`, `failed`).
- `--skip-group=<name>` — integration only. Exclude a group.
- `--multisite` — integration (incl. woo/base). Acceptance has its own task (`./do test:acceptance-multisite`). Vestigial on unit — accepted by the signature but the suite never boots WP, so it has no effect.
- `--stop-on-fail` — integration only. Halt on the first failure.
- `--skip-plugins` — integration, acceptance. Don't load extra plugins (WC, etc.).
- `--enable-hpos` / `--disable-hpos` / `--enable-hpos-sync` — integration (incl. variants), acceptance (incl. multisite). Toggle WooCommerce High-Performance Order Storage for the run. Use when your change touches WC order data paths.
- `--wordpress-version=<ver>` — integration, acceptance. Run against a specific WP version, including beta/RC — see [[mailpoet-beta-compat-test]].
- `--timeout=<seconds>` — acceptance, acceptance-multisite. Per-test timeout override.
- `--skip-deps` — integration, acceptance (incl. multisite). Skip in-container `composer install`. The `pnpm test:integration` / `:acceptance` wrappers (and their premium variants) pass this by default.
- `--xml[=path]` — all suites. Write JUnit XML output. Used by CI; rarely needed locally.

When the wrapper isn't enough — direct Codeception access, artifact inspection, or DB poking — shell in (see "Shelling in" below).

## Suite essentials

### Unit — `pnpm test:unit`

Host-side, no WP, no DB. Free plugin only (premium has no unit suite). Re-run only what failed last time with `cd mailpoet && ./do test:failed-unit` — Codeception keeps the failure list under `tests/_output/` (via the `@group failed` mechanism).

### Integration — `pnpm test:integration`

Real WP + DB, Docker-backed. The default integration run loads WooCommerce; two narrower entrypoints let you scope:

- `cd mailpoet && ./do test:woo-integration` — only WC-tagged tests, WC loaded.
- `cd mailpoet && ./do test:base-integration` — only non-WC tests, WC not loaded.

Pick `test:woo-integration` when iterating on WC integration code; pick `test:base-integration` when you suspect WC is contaminating an otherwise WC-independent test. Otherwise stick to the default.

Multisite: `pnpm test:integration --multisite`. Re-run failed: `cd mailpoet && ./do test:failed-integration`.

### Acceptance — `pnpm test:acceptance`

Selenium + Chrome in Docker. The slowest suite by a wide margin. Target the file you care about; do not run the whole thing on a whim.

- Multisite: `cd mailpoet && ./do test:acceptance-multisite --skip-deps --file=...`.
- Watch the run live: VNC to `localhost:5900`, password `secret` (macOS: `open vnc://localhost:5900`; Linux/Windows: any VNC client).
- **Failure artifacts** land in `mailpoet/tests/_output/` (premium equivalent under `mailpoet-premium/`): `*.fail.png` screenshots, `*.fail.html` page dumps, Codeception logs. Look here _before_ re-running — the screenshot usually tells you the story. For full failure investigation, hand off to [[debugging-failed-tests]].

### JavaScript — `pnpm test:javascript`

Mocha + Chai + Sinon, host-side, free only. The wrapper has no `--file` flag — it runs the whole `mailpoet/tests/javascript/` tree.

To focus on a single spec, either add `describe.only(...)` or `it.only(...)` to the spec and re-run (don't commit the `.only`), or invoke Mocha directly:

```bash
cd mailpoet
# Single file:
./node_modules/.bin/mocha --require tests/javascript/mocha-env.mjs \
  --extension spec.ts tests/javascript/path/to/foo.spec.ts

# Filter by test name across the tree:
./node_modules/.bin/mocha --require tests/javascript/mocha-env.mjs \
  --extension spec.ts --recursive tests/javascript --grep "my describe block"
```

Add `--inspect-brk` to the same command and attach Node DevTools (`chrome://inspect`) or a VS Code "Node: Attach" config to debug.

**Stale `assets/dist/`:** a handful of specs import from compiled output. If a test reports `X is not a function` against code that obviously exports `X`, run `pnpm compile:js` and retry before going hunting.

### Newsletter Editor (legacy) — `cd mailpoet && ./do test:newsletter-editor`

Mocha suite covering the Backbone newsletter editor under `mailpoet/tests/javascript-newsletter-editor/`. Free plugin only.

**Do not add new tests here.** The Backbone editor is being replaced by the block-based editor at `mailpoet/assets/js/src/mailpoet-email-editor-integration/`. Run this suite only when you've actually modified the legacy editor — otherwise it's slow noise.

### Performance — `cd mailpoet && ./do test:performance`

k6 + Playwright, Docker-backed. Free plugin only. Runs against a live MailPoet instance you supply (the test is the load generator, not a fixture). Three-step lifecycle:

```bash
cd mailpoet
./do test:performance-setup                                       # one-time prep (seed data, etc.)
./do test:performance --url=<wp-url> --us=<user> --pw=<pass>      # actual run
./do test:performance-clean                                       # tear down test data
```

Optional flags on `test:performance`:

- `--head` — run the Playwright browser headed (default headless).
- `--scenario=<name>` — restrict to one scenario from `mailpoet/tests/performance/scenarios.js`.
- Positional `<path>` (before `--`) — point at a different scenarios file.

Cloud mode kicks in automatically when `K6_CLOUD_TOKEN` is set; results upload to k6 Cloud instead of printing locally. Full signature: `testPerformance` in `mailpoet/RoboFile.php`.

## Shelling in

```bash
pnpm shell:test                                             # bash into tests_env wordpress container
cd /wp-core/wp-content/plugins/mailpoet                     # or mailpoet-premium
./do test:integration --skip-deps --file=tests/integration/...
```

Useful when:

- Running Codeception directly (`../tests_env/vendor/bin/codecept ...`) — e.g. for a flag the Robo wrapper doesn't expose.
- Inspecting DB state mid-debug via `wp db query "..."` against the test database.
- Iterating on the test runner itself.

### The `--skip-deps` footgun

When invoking `./do test:*` inside the container, **always pass `--skip-deps`**. Without it the prefixer runs again on PHP 8.4 and can wipe `mailpoet/vendor-prefixed/`. Recovery: `pnpm bootstrap`.

This is why the host-side `pnpm test:integration` / `pnpm test:acceptance` (and their `:premium` variants) pass `--skip-deps` automatically. `pnpm test:unit` and `pnpm test:javascript` don't need it — they don't run in Docker. Use `pnpm test:install-deps` only when you actually want to refresh the container's deps (e.g. after a `composer.json` bump).

## Common failure modes

| Symptom                                                 | Likely cause / fix                                                                                                                                                                        |
| ------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| "Cannot connect to the Docker daemon" on a Docker suite | Docker Desktop / OrbStack isn't running. Start it, then re-run.                                                                                                                           |
| First integration/acceptance run takes minutes to start | First-time container build/pull. Tail the test stack from the repo root: `docker compose -f tests_env/docker/docker-compose.yml logs -f` (not `pnpm env:logs` — that's the wp-env stack). |
| `vendor-prefixed/` disappeared after a test run         | Ran `./do test:*` without `--skip-deps` on PHP 8.4. Recover with `pnpm bootstrap`.                                                                                                        |
| Acceptance run starts but every test fails at login     | Test WordPress site is in a broken state. `cd mailpoet && ./do reset:test-docker` drops the test volumes (keeps images) and rebuilds.                                                     |
| Docker is genuinely confused (zombies, weird state)     | `cd mailpoet && ./do delete:docker` does the harder reset: stops containers, drops volumes, **removes images** (next run will re-pull/build, ~1–2min). Does not touch the wp-env stack.   |
| JS test errors with `X is not a function` for real code | `assets/dist/` is stale. Run `pnpm compile:js` and re-run.                                                                                                                                |
| WC-related integration failures (orders, HPOS)          | Toggle HPOS for the run: `--enable-hpos`, `--disable-hpos`, or `--enable-hpos-sync`.                                                                                                      |
| "Unknown option --file" or similar                      | Wrong wrapper for the suite (e.g. JavaScript has no `--file`). Check the Quick Reference.                                                                                                 |
| Test passes locally but failed on CI                    | Hand off to [[debugging-failed-tests]] with the CI job URL, branch name, failing test path, and any WP/WC version overrides the CI run used.                                              |

### Interrupting a long run

Ctrl-C is fine, but it can leave Selenium / Chrome containers running for an acceptance run. They usually self-correct on the next invocation; if things look off, `./do reset:test-docker` is the soft reset and `./do delete:docker` is the hard one.

## Verification

Read the final pass/fail summary line before reporting a run as green. "No output" or "command returned" is not evidence — look for `OK (N tests, M assertions)` (PHPUnit/Codeception) or the equivalent green line. For finishing-a-branch workflows, the canonical pre-push gate is `pnpm qa` plus the suite(s) touched by your change.
