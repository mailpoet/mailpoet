---
name: debugging-failed-tests
description: Use when investigating a failing test — reported via a CircleCI job/build URL, or output from a local test run. Triggers on phrases like "this test failed", "debug this CI failure", "why did the nightly fail?", "investigate the failure on <branch>", or any message pasting a CircleCI URL with failure context. Guides the investigator to diagnose, fix, and verify the fix locally. Stops there — committing, opening a PR, or writing a changelog is the caller's call (see `creating-pull-requests`, `writing-changelog`).
---

# Debugging Failed Tests

A test failed. The input is either a CircleCI URL or a failure dump pasted into chat. This skill takes you from "something is red" to "I have a fix and I've proven it works." It stops at "verified locally" — commits, PRs, and changelogs are the caller's call.

## Working assumption

Every failure is a real regression until evidence proves otherwise. Don't pre-emptively label something flaky.

## Principles

Three things shape every move below:

1. **Read the source before you run anything.** The test file plus the production code it exercises usually tells you what happened in under a minute. Reproducing locally — especially acceptance — costs minutes. Save the runs for verifying fixes or for when reading wasn't enough.

2. **Default suspicion is product code, not test.** When something looks flaky, trace the path end-to-end (every async call, every store action, every redirect). Ask: would a real user with a slow network hit this? If yes, the bug is in product code and the test is just exposing it. React re-render races and transient erroneous UI states are common acceptance-test culprits — fast clicks reveal what real users see momentarily. A timeout bump that hides a real race is worse than no fix.

3. **Prefer cheap signals.** Rich error message → open the file. Opaque acceptance failure → fetch artifacts (screenshots, page dumps) before reproducing. Reproduce only when the source doesn't answer the question, or to verify a fix. Reach for `git bisect` only when eyeballing the diff hasn't worked.

A premium-suite failure may originate in the free plugin — keep that possibility live when investigating premium-only reds.

## Inputs

- **A CircleCI URL.** Everything else (suite, plugin, WP/WC/PHP versions, beta/RC flag, branch, SHA, failing test) is derivable from the API. See `references/circleci.md`.
- **A failure dump from a local run.** Target branch is whatever `git rev-parse --abbrev-ref HEAD` reports.

## Where to start, by input

- **CircleCI URL** → fetch the tests endpoint first (`references/circleci.md`). It returns one row per test with `name`, `classname`, `result`, `message`. Filter to `result != "success"` to surface every non-passing row (`failure`, `error`, and `skipped`). MailPoet does not allow skipped tests on `trunk` or release branches outside `tests/_support/CheckSkippedTestsExtension`'s allowlist — so on those branches a `skipped` row is itself a signal worth investigating, not noise. If `message` names a `file:line`, jump to it. If the message is empty or generic and the suite is acceptance, fetch `*.fail.png` / `*.fail.html` artifacts before anything else.
- **Local failure dump** → open the file the error points at. You don't need CircleCI at all.
- **CircleCI URL, tests endpoint reports 0 failures, job still red** → it's a _non-test_ CI failure. Common causes: composer/npm install bombed, asset build broke, lint/QA gate caught something, OOM, or `infrastructure_fail`. Read the failing step's raw output and fix the build/install/lint issue directly. For `infrastructure_fail`, retry the job before investigating — it's CircleCI's problem. The investigation flow below doesn't apply.

## Investigation moves

Pick whichever the failure signal makes cheapest. Not a sequence.

**Existing fix in flight (trunk failures only).** `gh pr list --state open --search "<method-or-class-name>"`. Use the test method or class name, not the file path — `gh` searches title/body/branch, not paths. On feature-branch failures, skip — the failure is by definition tied to in-flight work that belongs to whoever owns the branch.

**What changed recently.** `git log --oneline -10 -- <test-file>` and the same for the production file the test exercises. Most regressions are in the last 1–2 commits to one of those. If neither shows anything, widen to the directory or walk the green→red commit range via the compare API:

```
gh api repos/mailpoet/mailpoet/compare/<last-green-sha>...<failing-sha>
```

Lists every file changed across the range without needing a local checkout of the failing SHA. The last-green SHA comes from the CircleCI insights endpoint (`references/circleci.md`).

**Environment drift.** WP/WC/PHP versions print at the start of the failing step in the CircleCI log — the Codeception entrypoint logs `WORDPRESS VERSION:`, `TEST RUNNER PHP VERSION:`, and the WooCommerce version. If the run is against a WordPress or WooCommerce beta/RC (job name often contains `_wordpress_beta` / `_woocommerce_beta`, or the version string ends in `-beta.N` / `-RC1`), see `references/beta-rc-failures.md`.

**Frequency check (transient-looking failures).** When the failure looks environmental — network blip, external service 4xx/5xx, container-level glitch — the rerun-vs-fix call depends on how often it's happening. Hit the CircleCI insights endpoint (`references/circleci.md`) for the same workflow on the same branch: if the same failure shape recurs across multiple recent runs, fix it at the source (mock the outbound call, stub the service, filter the gate). If it's a one-off in months of green runs, recommend a rerun and move on.

**`git bisect` between last-green and failing SHA.** Only when eyeballing the diff didn't work. The form wired to project commands:

```
git bisect run pnpm test:<suite> --file=<path-to-failing-test>
```

For acceptance the per-iteration cost makes this a last resort. `git bisect reset` when done.

## Reproducing locally

Repro commands per suite (and premium variants, debug/multisite flags) live in the [[running-tests]] skill. Defer there.

Don't reproduce as a habit — reproduce when the source doesn't tell you enough, or when you're verifying a fix.

## Verifying the fix

Before claiming done, prove both directions:

1. **Reproduce the failure on the un-fixed code.** `git stash --include-untracked` your change (the `--include-untracked` is important — a fix that adds a new fixture or helper file would otherwise stay in place and the run wouldn't actually be un-fixed). Run the failing test, confirm it fails the same way it failed in CI.
2. **Reapply the fix.** `git stash pop`, run the failing test again, confirm it passes.

A green run on its own does not prove your change is what fixed it. This matters most when the failure looked flaky or intermittent — those are the cases most likely to pass for unrelated reasons.

## When NOT to apply a fix

Stop and report findings — root cause, why no fix was applied, options considered, confidence, unknowns — when:

- The bug is in `vendor/`, `vendor-prefixed/`, `lib-3rd-party/`, `generated/`, or WordPress / WooCommerce core.
- The fix needs a DB migration, schema change, public API change (`lib/API/MP/`), or modifications to `.wp-env.json`, `tests_env/`, or CI config. These are "ask first" territory per project guidelines.
- The fix requires a judgment call that should be a human/team decision (which deprecated API to migrate to, which behaviour to preserve, etc.).
- You can't reproduce locally after reasonable effort AND the CircleCI artifacts don't clarify enough — you'd be guessing.
- The failure-data sources contradict each other (e.g. the `failed` artifact lists a test but the tests endpoint and JUnit report it as passing, or there's no `.fail.png` / `.fail.html` for an assertion). The signal is broken — stop and report rather than chase a guess.

DI container changes are fine — they don't belong on this list.

## Tools worth knowing

- `circleci-api.sh` — the auth wrapper for CircleCI. Treat as a black box: don't curl CircleCI directly, don't read or pass the token yourself, don't paste the wrapper's error output into chat or commits. If the wrapper says the token isn't configured, stop and report exactly that — see the script header for setup.
- `gh api compare` — green→red file enumeration without a local checkout.
- `git bisect run pnpm test:<suite> --file=<...>` — the form wired to project commands.

## Branch hygiene

If the failure ran against `trunk`, use the [[starting-branch]] skill to create a fix branch **before** editing — never land fixes directly on `trunk`. If it ran against a feature branch, `git fetch && git switch <branch>` and work on top of it; the fix belongs with the in-flight work.

For local failures, the target is whatever branch you're already on.

## Outcome

Once the targeted suite is green locally and you've verified both directions, hand back with a short summary: failing test, root cause, what was changed, what was verified. Likely next-step skills: [[creating-pull-requests]], [[writing-changelog]], [[mailpoet-dev-cycle]].

## Related skills

- [[running-tests]] — exact repro commands per suite, premium variants, debug/multisite modes.
- [[starting-branch]] — used when the failure originated on `trunk`.
- [[creating-pull-requests]] — draft PR after the fix.
- [[writing-changelog]] — when the fix is user-facing.
- [[mailpoet-beta-compat-test]] — broader beta/RC compatibility testing.
