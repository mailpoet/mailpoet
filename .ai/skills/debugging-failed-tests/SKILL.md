---
name: debugging-failed-tests
description: Use when investigating a failing test — reported via a CircleCI job/build URL, or output from a local test run. Triggers on phrases like "this test failed", "debug this CI failure", "why did the nightly fail?", "investigate the failure on <branch>", or any message pasting a CircleCI URL with failure context. Guides the investigator from failure signal → reproduction → root cause → fix applied and verified locally. Stops there — committing, opening a PR, or writing a changelog is the caller's call (see `creating-pull-requests`, `writing-changelog`).
---

# Debugging Failed Tests

## Overview

A test failed and we need to find out why and fix it. The failure can come from a pasted CircleCI job URL, or a local run that just blew up. This skill walks through gathering context, reproducing the failure locally, forming a root-cause hypothesis, applying a fix, and verifying it with the targeted test suite. **Stops there** — whether to commit, open a PR, or add a changelog is the caller's call (see `creating-pull-requests`, `writing-changelog`, `mailpoet-dev-cycle`).

**Working assumption:** every failure is a real regression until evidence proves otherwise. Do not pre-emptively label something flaky.

## Inputs

One of:

- **A CircleCI build/job URL.** Everything else (suite, plugin, WP/WC/PHP versions, beta/RC flag, branch, SHA) is derived in Step 1.
- **Failing test output from a local run.** No URL; the target branch is the current branch (`git rev-parse --abbrev-ref HEAD`).

### CircleCI access

CircleCI calls go through the `.ai/skills/debugging-failed-tests/circleci-api.sh` wrapper. It handles auth — treat it as a black box. Do **not** invoke `curl` against CircleCI directly, do **not** look up the token yourself (env vars, config files, the wrapper script — anywhere), and do **not** copy the wrapper's error output verbatim into chat or commits. If the wrapper exits saying the token isn't configured, stop and report "CircleCI token not configured locally — see `.ai/skills/debugging-failed-tests/circleci-api.sh` for setup" (no paths, no env-var names, no guesses).

See `references/circleci-api.md` for the useful endpoints (job, tests, artifacts, workflow siblings, insights). Read it once — it answers most "which endpoint do I call?" questions.

## Heads-up: not every failed CI job is a test failure

A failed CircleCI job can also be: a composer/npm install failure, an asset-build failure, a lint/QA gate failure, an OOM, or `infrastructure_fail`. In those cases skip Steps 3–6 — read the failing step's output directly, fix the build/install/lint issue, and rerun. The "When NOT to apply a fix" rules still apply. `infrastructure_fail` is CircleCI's problem, not yours; retry the job before debugging.

## Step 0: Is this already known?

Do this **first** — before any CI fetching. It is cheap and routinely saves time: another engineer may be in flight on the same fix. Skipping straight to investigation is the most common time-waster in this workflow.

1. **Existing fix in flight** — `gh pr list --state open --search "<method-or-class-name>"`. Use the test method name _or_ the class name (drop `Test.php` / `Cest.php`). Don't paste the full path — `gh` searches title/body/branch, not paths.
2. **Is this test brand new?** — `git log --diff-filter=A -- <test-file-path>` shows the commit that introduced the file. If the file was added in the failing range, the test itself may simply be broken from birth.
3. **Recent author of the production code under test** — once you know what the test exercises (see Step 4.1), `git log -1 --format='%an' -- <production-file>` gives you the last author. Then `gh pr list --state open --author <github-handle>` checks whether they already have something in flight. (Skip this in Step 0 if you don't yet know the target — circle back from Step 4.)

If a relevant PR exists, stop and report the link instead of starting parallel work.

## Step 1: Gather failure context

1. **Get structured failure data first.** Hit the `tests` endpoint — it returns one row per test with `name`, `classname`, `result`, `message`. Filter to `result != "success"` and you have your repro list. Much cheaper than scrolling step logs. (See `references/circleci-api.md`.) Capture: failing test name(s), the commit SHA the job ran against, **and the branch the job ran against** (`pipeline.vcs.branch` on the job payload).
2. **If the `message` names a `file:line`, open it immediately.** A rich exception (`TimeoutException at FooCest.php:32`, an explicit assertion line, etc.) usually carries enough to hypothesise from the test source alone — artifacts and step logs add little. Save the heavier fetching for opaque or empty messages.
3. **Acceptance failures with opaque messages: fetch artifacts.** When the message is empty or unhelpful (common for selector timeouts), Codeception's `*.fail.png` screenshots and `*.fail.html` page dumps usually reveal the issue without re-running locally (missing element, wrong text, redirect, JS error). See `references/circleci-api.md` for the artifacts endpoint and download recipe.
4. **Identify the suite** from the failing path:
   - `tests/unit/*Test.php` → unit
   - `tests/integration/*Test.php` → integration
   - `tests/acceptance/*Cest.php` → acceptance
   - `tests/javascript/*.spec.ts` → JavaScript
5. **Identify the plugin:** path under `mailpoet/` (free) or `mailpoet-premium/` (premium). Match the plugin to the repo — never mix free fixes into premium changes or vice versa.
6. **Capture the WP / WC / PHP versions.** The Codeception entrypoint (`tests_env/docker/codeception/docker-entrypoint.sh`) prints them near the start of the failing step's output — look for `WORDPRESS VERSION:`, `TEST RUNNER PHP VERSION:`, and the WooCommerce zip/download lines. The v2 API does not surface versions; the easiest source is the raw step log via the job's web UI.
7. **Check sibling jobs in the same workflow — but only if they fail with the _same_ symptom.** Same test name, same error class, same step. If multiple integration shards blow up on the same fixture, or all WC-beta jobs go red together, the root cause is shared and the investigation collapses. If the sibling failures look unrelated (different suite, different error), treat them as independent — do **not** conflate them. See `references/circleci-api.md` for the workflow/job endpoints.
8. **Flag beta/RC runs.** If a captured version is a beta or RC (e.g. `9.5.0-beta.1`, `6.8-RC1`), or the job name contains `_wordpress_beta` / `_woocommerce_beta`, mark the run for the dedicated Step 4 branch.
9. **Now open the test source and the production code it exercises** — _before_ moving to Step 2. Most failures are diagnosable from the test source plus the rich error message; you'll waste time if you keep churning CI endpoints with the test file still unopened. See the "Finding the production code the test exercises" hint under Step 4.1 for how to pick the right production file.

## Step 2: Get onto the right branch

The fix needs to land on the branch where the failure occurred — _if_ the caller later decides to commit.

- **Job ran against `trunk`** → use the `starting-branch` skill to create a fix branch. If the failing test's last touching commit references a Linear ticket (e.g. `git log -1 --format=%B -- <test-file> | grep -oE 'MAILPOET-[0-9]+'`), pass that to `starting-branch`. Otherwise a short descriptive slug like `fix/<suite>-<short-test-name>` is fine. (We never want `HEAD` to be `trunk` while editing.)
- **Job ran against a feature branch** → `git fetch && git switch <branch>`. Do not create a fresh fix branch on top — the failure belongs to in-flight work and the fix should land on the same branch.

For local failures with no CI URL, the target branch is whatever `git rev-parse --abbrev-ref HEAD` reports.

## Step 3: Reproduce locally

Non-negotiable before forming a root-cause hypothesis. Repro commands per suite live in the `running-tests` skill.

If **3 consecutive local runs pass**, that is a strong signal of environment drift or true flakiness — proceed to Step 4 with that hypothesis. Do not assume the test is fine just because it's green on your laptop.

## Step 4: Form a hypothesis — investigate in this priority order

These are the common root causes, in rough order of frequency.

### 1. Recent commit regression (start here)

Identify what changed recently. Walk the recent history of the failing test file and the production code it exercises (the last ~30 commits is usually plenty). If Step 1 surfaced a last-green SHA via the insights endpoint, look at the diff across the green→red range — that's a much tighter window than chasing the whole history.

For a broader sweep — especially useful when a non-test job fell over and the smoking gun is often a newly-added test file missing an `@group` annotation or guard — enumerate every file changed in the green→red range via GitHub's compare API. Works without a local checkout of the failing SHA:

`gh api repos/mailpoet/mailpoet/compare/<last-green-sha>...<failing-sha>`

**Finding the production code the test exercises:**

- Read the test's `use` statements at the top — the namespaced classes under test are usually obvious.
- For a `FooTest`, the target class is almost always `Foo`.
- For integration tests that exercise multiple classes, look at what the test instantiates directly or resolves from the DI container.

If the suspect range is wide and the diff is too noisy to read by eye, run `git bisect` between the failing and last-green SHAs — it lands on the offending commit deterministically. The non-obvious bit is wiring `bisect run` to the project's pnpm test command:

`git bisect run pnpm test:<suite> --file=<path-to-failing-test>`

Reset with `git bisect reset` when done. For acceptance/integration the `bisect run` step is slow per iteration; only reach for it when eyeballing the diff hasn't worked.

### 2. Flaky test

Suspect this only if (a) local re-runs are inconsistent across the 3 attempts from Step 3, (b) CircleCI history for the same test shows recent intermittent failures (insights endpoint — see `references/circleci-api.md`), or (c) the failure mode points to timing/ordering/external state.

**Before settling on a test-only fix (e.g. "just bump the timeout"), do this:**

1. Read the _full_ client/server code path the test exercises end-to-end — not just the test. Trace every async call, every store action, every redirect, every page reload.
2. Ask: would a _real user_ hit this race if the network/server were slow? If yes, the production code has the bug — harden it. The test fix is secondary.
3. Only after the above: if the production code is genuinely correct and the test simply made unsafe timing assumptions, harden the test (longer timeout, explicit `waitFor*`, etc.).

A timeout bump that papers over a real production race is worse than no fix — it hides the bug from CI and from real users.

### 3. Environment drift

Dependency bumps (WP, WC, PHP), test container changes, generated/prefixed file mismatches. Diff the environment-affecting files across the green→red range — `composer.json`, `composer.lock`, `package.json`, `pnpm-lock.yaml`, `.wp-env.json`, and anything under `tests_env/`.

### 4. Pre-existing latent bug exposed

A seemingly unrelated change altered test order, fixture data, or timing, exposing an older bug. Treat the older bug as the real fix target; the recent commit is just the trigger.

### 5. Beta / RC of WordPress or WooCommerce

Only when Step 1 flagged the run as beta/RC. See the dedicated section below — investigate both directions, but **the default fix target is still our plugin or test**, not the upstream beta.

### Beta / RC runs (WordPress or WooCommerce)

If the failing CI job ran against a beta or release candidate:

1. **Confirm the beta/RC version** from the entrypoint output. Note the exact version string.
2. **Reproduce locally against the same version** — `pnpm test:<suite> --wordpress-version=<ver>` (or the WC equivalent; see the `running-tests` skill for the full flag surface). If it passes against the stable version but fails against the beta/RC, the version is implicated.
3. **Read the release notes** for breaking changes or deprecations in the area the failing test exercises (hooks, REST routes, function signatures, block API, HPOS, etc.):
   - WordPress betas/RCs: [`wordpress.org/news`](https://wordpress.org/news/category/releases/), [Trac](https://core.trac.wordpress.org/), and the [`WordPress/wordpress-develop`](https://github.com/WordPress/wordpress-develop) GitHub repo.
   - WooCommerce betas/RCs: [`woocommerce/woocommerce` releases](https://github.com/woocommerce/woocommerce/releases).
4. **Search for an existing upstream issue** describing the same regression or BC change. **Do not file new upstream issues** — only reference existing ones in your report. If you find nothing, say so explicitly.
5. **Decide the fix direction — in this priority:**
   - **Adapt our code or test** to the new behaviour if it's a legitimate change (deprecation, new signature, intentional behaviour change). Default and preferred outcome.
   - **Ship a plugin-side workaround** if the change looks like an unintentional upstream regression but adapting cleanly isn't feasible. Guard the workaround with a version check (`version_compare()` or feature detection) so it activates only on affected versions — never branch on "is this a beta".

## Step 5: Implement the fix

- Prefer product-code fixes over test-only fixes when the underlying problem is a real bug.
- Test-only hardening is appropriate when the production code is correct and the test simply made unsafe assumptions (ordering, timing, isolation).
- If the only viable fix lands in restricted territory, **stop** — see "When NOT to apply a fix" below.

## Step 6: Verify locally

Run the failing suite scoped to the failing test — exact commands per suite (and premium variants) are in the `running-tests` skill. The targeted suite must be green before reporting the fix as done.

## When NOT to apply a fix

Stop and report findings — root cause, why no fix was applied, suggested next steps — when:

- The root cause is in `vendor/`, `vendor-prefixed/`, `generated/`, `lib-3rd-party/`, WordPress core, or a third-party library.
- The fix requires a DB migration, schema change, public API change (`lib/API/MP/`), DI container change, or modifications to `.wp-env.json` / `tests_env/` / CI config. (These are "Ask First" territory per the project guidelines.)
- The failure cannot be reproduced locally after reasonable effort _and_ the CircleCI artifacts do not clarify the cause.

In each case, the report should make a reviewer's life easy: link to the failure, summarise the root cause, list the options considered, and call out confidence and unresolved questions.

## Suite-specific hints

### Unit (`tests/unit/`)

Pure PHP — failures are almost always logic, mock drift, or static state leakage. Common causes: mock signatures drifted from real classes after a refactor; direct WP function calls bypassing the `WP\Functions` wrapper; static properties / singletons not reset between tests.

### Integration (`tests/integration/`)

Real DB + WP via the `tests_env/` docker-compose stack (separate from wp-env). `pnpm shell:test` drops into the tests_env WordPress container if you need to inspect DB state directly. Common causes: Doctrine entity ↔ migration drift; fixture/teardown leaks between tests; assumptions about row order or auto-increment IDs. **Never** run `./do test:integration` without `--skip-deps` — it can wipe `vendor-prefixed/` on PHP 8.4. Use `pnpm test:integration` (which passes `--skip-deps` by default).

### Acceptance (`tests/acceptance/`)

Selenium + browser via tests_env. **The slowest suite to reproduce** — that's why Step 1.2 says to fetch artifacts first. Common causes: timing / missing `waitFor*` calls; selectors broken by recent React/UI changes; React-rendered text changes that break text-based locators. If it looks like timing flakiness, investigate whether the production code has a real race (e.g. AJAX response handling order) before adding a wait.

### JavaScript (`tests/javascript/`)

Mocha runs the whole suite — isolate the failing spec from the output. Common causes: TypeScript type drift after shared package changes; stale fixture/mock data after API or entity shape changes; stale `assets/dist/` when tests import from compiled output (run `pnpm compile:js`).

### Premium variants

A failure in the premium suite may indicate a regression in the free plugin (premium extends free). When the root cause turns out to be in `mailpoet/`, the fix lands in the free repo, and the premium repo may need a companion change.

## Outcome

Once the targeted suite is green locally, this skill is done. Hand back to the caller with a short summary: failing test, root cause, what was changed, what was verified. Likely next-step skills the caller may want: `creating-pull-requests`, `writing-changelog`, `mailpoet-dev-cycle`.

## Related Skills

- `running-tests` — exact repro commands per suite.
- `starting-branch` — used in Step 2 when the failure originated on `trunk`.
- `creating-pull-requests` — draft PR after the fix.
- `writing-changelog` — if the fix is user-facing.
- `mailpoet-beta-compat-test` — when failures stem from a WP/WC beta or RC.
