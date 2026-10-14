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

The shared script names used across our repos are aliases: `pnpm test:php` runs `test:unit`, `pnpm test:js` runs `test:javascript`, `pnpm test:e2e` runs `test:acceptance`.

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

Premium variants: see [Premium](#premium).

**Single test method** (unit, integration, acceptance): append `:methodName` to the file path.

```bash
pnpm test:integration --file=tests/integration/Logging/LogHandlerTest.php:testItStoresLog
pnpm test:acceptance --file=tests/acceptance/Misc/WordPressSiteEditorCest.php:editWithFSE
```

## Flags

`pnpm` forwards extra args, so every flag works through the wrapper: `--file=<path>[:method]`, `--debug`, `--group` / `--skip-group`, `--multisite`, `--stop-on-fail`, `--skip-plugins`, the HPOS toggles, `--blockbased-theme`, `--wordpress-version`, `--timeout`, `--skip-deps` and `--xml`. Which suite takes which flag: [flags.md](flags.md).

## Suite essentials

### Unit — `pnpm test:unit`

Host-side, no WP, no DB. Free plugin only (premium has no unit suite). Re-run only what failed last time with `cd mailpoet && ./do test:failed-unit` — Codeception keeps the failure list under `tests/_output/` (via the `@group failed` mechanism).

### Integration — `pnpm test:integration`

Real WP + DB, Docker-backed. The default integration run loads WooCommerce; two narrower entrypoints let you scope:

- `cd mailpoet && ./do test:woo-integration` — only WC-tagged tests, WC loaded.
- `cd mailpoet && ./do test:base-integration` — only non-WC tests, WC not loaded.

Pick `test:woo-integration` when iterating on WC integration code; pick `test:base-integration` when you suspect WC is contaminating an otherwise WC-independent test. Otherwise stick to the default.

Multisite: `pnpm test:integration --multisite`. Re-run failed: `pnpm test:integration --group=failed` (runs in Docker like any integration run).

### Acceptance — `pnpm test:acceptance`

Selenium + Chrome in Docker. The slowest suite by a wide margin. Target the file you care about; do not run the whole thing on a whim.

- Multisite: `cd mailpoet && ./do test:acceptance-multisite --skip-deps --file=...`.
- Watch the run live: VNC to `localhost:5900`, password `secret` (macOS: `open vnc://localhost:5900`; Linux/Windows: any VNC client).
- **Failure artifacts** land in `mailpoet/tests/_output/` (premium equivalent under `mailpoet-premium/`): `*.fail.png` screenshots, `*.fail.html` page dumps, Codeception logs. Look here _before_ re-running — the screenshot usually tells you the story. For full failure investigation, hand off to [[debugging-failed-tests]].

### JavaScript — `pnpm test:javascript`

Mocha + Chai + Sinon, host-side, free only. The wrapper has no `--file` flag — it runs the whole `mailpoet/tests/javascript/` tree.

To focus on a single spec, either add `describe.only(...)` or `it.only(...)` to the spec and re-run (don’t commit the `.only`), or invoke Mocha directly:

```bash
cd mailpoet
# Single file:
./node_modules/.bin/mocha --require tests/javascript/mocha-env.mjs \
  --extension spec.ts tests/javascript/path/to/foo.spec.ts

# Filter by test name across the tree:
./node_modules/.bin/mocha --require tests/javascript/mocha-env.mjs \
  --extension spec.ts --recursive tests/javascript --grep "my describe block"
```

Add `--inspect-brk` to the same command and attach Node DevTools (`chrome://inspect`) or a VS Code “Node: Attach” config to debug.

**Stale `assets/dist/`:** a handful of specs import from compiled output. If a test reports `X is not a function` against code that obviously exports `X`, run `pnpm compile:js` and retry before going hunting.

### Newsletter Editor (legacy) — `cd mailpoet && ./do test:newsletter-editor`

Mocha suite covering the Backbone newsletter editor under `mailpoet/tests/javascript-newsletter-editor/`. Free plugin only.

**Do not add new tests here.** The Backbone editor is being replaced by the block-based editor at `mailpoet/assets/js/src/mailpoet-email-editor-integration/`. Run this suite only when you’ve actually modified the legacy editor — otherwise it’s slow noise.

### Performance — `cd mailpoet && ./do test:performance`

k6 + Playwright load tests against a live MailPoet instance you supply. Free plugin only. Setup, flags and cloud mode: [performance.md](performance.md).

## Shelling in

```bash
pnpm shell:test                                             # bash into tests_env wordpress container
cd /wp-core/wp-content/plugins/mailpoet                     # or mailpoet-premium
./do test:integration --skip-deps --file=tests/integration/...
```

Useful when:

- Running Codeception directly (`../tests_env/vendor/bin/codecept ...`) — e.g. for a flag the Robo wrapper doesn’t expose.
- Inspecting DB state mid-debug via `wp db query "..."` against the test database.
- Iterating on the test runner itself.

### The `--skip-deps` footgun

When invoking `./do test:*` inside the container, **always pass `--skip-deps`**. Without it the prefixer runs again on PHP 8.4+ and can wipe `mailpoet/vendor-prefixed/`. Recovery: `pnpm bootstrap`.

This is why the host-side `pnpm test:integration` / `pnpm test:acceptance` (and their `:premium` variants) pass `--skip-deps` automatically. `pnpm test:unit` and `pnpm test:javascript` don’t need it — they don’t run in Docker. Use `pnpm test:install-deps` only when you actually want to refresh the container’s deps (e.g. after a `composer.json` bump).

## Common failure modes

Docker not running, a slow first run, a missing `vendor-prefixed/`, login failures, stale `assets/dist/`, HPOS-related failures and stuck containers: see [failure-modes.md](failure-modes.md) before re-running.

## Verification

Read the final pass/fail summary line before reporting a run as green. “No output” or “command returned” is not evidence — look for `OK (N tests, M assertions)` (PHPUnit/Codeception) or the equivalent green line. For finishing-a-branch workflows, the canonical pre-push gate is `pnpm qa` plus the suite(s) touched by your change.

## Premium

From the repo root, replace `test:integration` / `test:acceptance` with `test:integration:premium` / `test:acceptance:premium`. Premium has no unit or JavaScript suite. Paths are relative to whichever plugin is being tested. Inside `mailpoet-premium/`, `pnpm test:php` runs the integration suite, so it needs the `tests_env/` Docker stack.
