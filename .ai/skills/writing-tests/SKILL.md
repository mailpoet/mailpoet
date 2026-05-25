---
name: writing-tests
description: 'Use when authoring tests for MailPoet — adding a new test case, picking the right test type, choosing a name, structuring the file, deciding what belongs in unit vs integration vs acceptance. For invoking the test runner (running a file, the whole suite, premium variants, debug mode) see running-tests. For investigating a CI failure see debugging-failed-tests.'
---

# Writing Tests

## Overview

MailPoet has six test suites across two plugins. This skill is about **authoring** them — where the file belongs, what to extend, how to name and structure it, what kind of test belongs where. For invoking the test runner, see [[running-tests]].

## Guidelines

- **Prefer smaller tests over big ones.** Keep them focused, short, and linear. Avoid `if` / `for` / `while` inside a test — if you need a loop you probably need separate test cases instead. Why: loops hide which case actually failed, and one fix accidentally masks another.
- **Use descriptive test names.** The name should make it obvious from CI output what failed and why. Describe the scenario and expected outcome, not the method being tested. A reader who has never seen the file should be able to guess the intent from the name alone.
- **Prefer self-explanatory tests over comments.** First try to refactor — better naming, extract helpers, split into smaller tests — so the comment becomes unnecessary. A comment that survives that pass is usually worth keeping; one that doesn't is a code smell pointing at the test itself.
- **In acceptance tests use `$i->wantTo(...)` instead of comments.** `wantTo` text shows up in the Codeception output, plain comments don't. The output is the only thing a future debugger sees.
- **Prefer unit > integration > acceptance.** Unit tests are fast and cheap; acceptance tests are slow, flaky, and expensive to maintain. Only reach for acceptance when you genuinely need a real browser flow.
- **Avoid brittle selectors in acceptance tests.** Long CSS chains and 3rd-party DOM structure break on library updates. Prefer `data-automation-id` attributes, ARIA roles, or short stable selectors. If a 3rd-party component doesn't expose good hooks, add a `data-automation-id` (or a thin wrapper) in the production code so the test has something stable to grab.
- **Defend against flakiness in acceptance tests.** Always wait for the page / element to be ready before interacting (`waitForElement`, `waitForText`, …) — never assume the page is loaded. When writing a test, deliberately ask "what could make this fail intermittently?" and guard against it.
- **Always run the test before committing.** Never commit a test you haven't seen pass at least once. If it can't be run locally, say so out loud — don't assume it works.
- **Prefer TDD.** Write the failing test first, see it fail (this proves the test actually tests something), then write the code that makes it pass.

## Picking the right suite

| You need to test…                                                   | Use                           | Why                                                                           |
| ------------------------------------------------------------------- | ----------------------------- | ----------------------------------------------------------------------------- |
| Pure PHP logic with no WP / DB dependency                           | Unit                          | Fastest, no Docker, runs on the host                                          |
| Behaviour that hits WP APIs, the DB, or our Doctrine repositories   | Integration                   | Real WordPress + DB inside Docker                                             |
| A real browser flow (forms, redirects, JS, multi-page interactions) | Acceptance                    | Only place to drive an actual browser                                         |
| Frontend React/TS logic without a real browser                      | JavaScript                    | Mocha-based, host-side                                                        |
| The legacy Backbone newsletter editor                               | _do not write new tests here_ | The Backbone editor is being replaced — extend the block-based editor instead |
| Load / scale behaviour                                              | Performance                   | k6 + Playwright; only for perf work                                           |

When two options would work, choose the one further up the table — cheaper to run, cheaper to maintain.

## Where new tests go and what they extend

### PHP unit (`mailpoet/tests/unit/`)

- File pattern `*Test.php`, base class `MailPoetUnitTest`.
- **Free plugin only** — premium has no unit suite. If your test needs to live in `mailpoet-premium/`, it has to be an integration test.
- No WordPress runtime, no DB. Mock the `WP\Functions` wrapper for any WP call (which is why production code must go through that wrapper — see AGENTS.md).

### PHP integration (`mailpoet/tests/integration/` and `mailpoet-premium/tests/integration/`)

- File pattern `*Test.php`, base class `\MailPoetTest` (extends Codeception's WP integration).
- WordPress and DB are live — write tests against the real Doctrine repositories where you can. Mock the seams that aren't under test.
- Multisite-specific behaviour: there's a multisite mode (see `running-tests` for how to invoke it); when authoring, guard multisite-only code paths so non-multisite runs of the same file still pass.

### PHP acceptance (`mailpoet/tests/acceptance/` and `mailpoet-premium/tests/acceptance/`)

- File pattern `*Cest.php`. Each test method takes the `AcceptanceTester $i` parameter.
- Use `$i->wantTo("describe the scenario")` at the top of each method — it lands in the run output and is the only documentation a future debugger sees.
- Anchor on `data-automation-id` / ARIA selectors, not DOM structure.
- Always wait explicitly for the element you're about to interact with.

### JavaScript (`mailpoet/tests/javascript/`)

- File pattern `*.spec.ts`. Mocha + Chai + Sinon.
- **Free plugin only.**
- Test logic, not the DOM. For DOM-level flows, prefer an acceptance test.

### Test data builders

Place reusable test fixtures under `tests/DataFactories/`. If you find yourself constructing the same shape twice, lift it into a factory the next test in this area will thank you for.

## Common mistakes when authoring tests

- **Writing the test after the code "just to have a test".** That doesn't prove the test tests anything; tests-after answer "what does this code do?" instead of "what should this code do?". Write the failing test first.
- **Picking acceptance for something a unit test could cover.** Acceptance tests are the most expensive thing you own. Push every assertion as far down the pyramid as it can go.
- **Asserting on long error message strings.** Error text changes; assert on the structured behaviour (exception type, status code, stored DB state) instead.
- **Sharing state across test methods within a file.** Each test must set up and tear down its own state. State leakage between tests is the #1 cause of "passes alone, fails in the suite" reports.
- **Calling WordPress functions directly in production code.** Test pain here is a code smell — the production code should go through `WP\Functions` so the test can mock it. Fix the production code rather than working around it in the test.

## Related skills

- [[running-tests]] — how to actually invoke the test runner (single file, whole suite, premium variants, debug mode, multisite, Docker shell).
- [[debugging-failed-tests]] — investigating a CircleCI failure and shipping a fix.
- [[mailpoet-dev-cycle]] — the broader lint/format/test loop you should run before pushing.
