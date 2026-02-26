---
name: writing-tests
description: 'Use when writing, running, or debugging tests. Use when asked to add test coverage, fix a failing test, or run a test suite.'
---

# Writing and Running Tests

## Overview

MailPoet has six test types across two plugins (free `mailpoet/` and premium `mailpoet-premium/`).

**Running tests:** Unit and JavaScript tests run on the host machine from the plugin directory (e.g. `mailpoet/`). Integration, acceptance, and performance tests require Docker and must be run from the monorepo root.

## Guidelines

- **Prefer smaller tests over big ones.** Keep tests focused, short, and linear. Avoid control structures like `if`, `for`, or `while` in tests — if you need a loop, you probably need separate test cases instead.
- **Use descriptive test names.** The test name should make it obvious what failed and why when you read it in CI output. Describe the scenario and expected outcome, not the method being tested.
- **Prefer self-explanatory tests over comments.** Comments can be useful, but first try to refactor the test so the comment isn't needed — better naming, extracting helpers, or splitting into smaller tests.
- **In E2E tests use `$i->wantTo(...)` instead of comments** — `wantTo` output is visible in test results, comments are not.
- **Prefer unit > integration > E2E.** Unit tests are fast and cheap. E2E tests are slow and expensive — don't add them unless you need to test a real browser flow.
- **Avoid brittle selectors in E2E tests.** Don't rely on long CSS selector chains or DOM structure of 3rd party components — these break when libraries update. Prefer `data-automation-id` attributes, ARIA roles, or short stable selectors. If a 3rd party component doesn't expose good hooks, add a `data-automation-id` or wrapper in the production code.
- **Fight flakiness in E2E tests.** Always wait for the page/element to be ready before interacting. Use `waitForElement`, `waitForText`, or similar helpers — never assume the page is loaded. Think about what could make the test fail intermittently and guard against it.
- **Always run tests before committing.** Never commit a test you haven't seen pass. If a test can't be run locally, say so — don't assume it works.
- **Prefer TDD.** Write the test first, see it fail, then write the code that makes it pass. The failing test proves the test actually tests something.

## Test Types

### PHP Unit Tests

Fast, isolated tests. No WordPress, no database. Run directly on the host machine from `mailpoet/`. **Free plugin only** — premium has no unit tests.

- **Location:** `mailpoet/tests/unit/`
- **File pattern:** `*Test.php`
- **Base class:** `MailPoetUnitTest`
- **Run all:** `./do test:unit`
- **Run one file:** `./do test:unit --file tests/unit/WooCommerce/TransactionalEmails/FontFamilyValidatorTest.php`
- **Debug mode:** `./do test:debug-unit --file tests/unit/...`
- **Re-run failed:** `./do test:failed-unit`

### PHP Integration Tests

Tests with WordPress and database, run inside Docker. Slower than unit tests. Run from the monorepo root.

- **Location:** `mailpoet/tests/integration/` and `mailpoet-premium/tests/integration/`
- **File pattern:** `*Test.php`
- **Base class:** `\MailPoetTest` (extends Codeception)
- **Run all:** `./do test:integration --skip-deps`
- **Run one file:** `./do test:integration --skip-deps --file tests/integration/Logging/LogHandlerTest.php`
- **Debug mode:** `./do test:debug-integration --file tests/integration/...`
- **Re-run failed:** `./do test:failed-integration`
- **Variants:**
  - `./do test:woo-integration` — with WooCommerce loaded
  - `./do test:base-integration` — without WooCommerce
  - `./do test:multisite-integration` — WordPress multisite

Use `--skip-deps` to avoid rebuilding Docker containers every run.

### PHP Acceptance Tests (E2E)

Browser-based end-to-end tests using Selenium and Chrome in Docker. **The slowest test type** — only add these when testing a real browser flow that can't be covered by unit or integration tests. Run from the `mailpoet/` plugin directory.

- **Location:** `mailpoet/tests/acceptance/` and `mailpoet-premium/tests/acceptance/`
- **File pattern:** `*Cest.php`
- **Run all:** `./do test:acceptance --skip-deps`
- **Run one file:** `./do test:acceptance --skip-deps --file tests/acceptance/Misc/WordPressSiteEditorCest.php`
- **Multisite:** `./do test:acceptance-multisite --skip-deps --file ...`
- **Reset Docker:** `./do delete:docker` — if you get unexpected errors, delete the Docker runtime and start fresh
- **Debug with pause:** Add `$i->pause();` in your test to pause execution and inspect the browser state
- **Watch tests in browser:** The browser runs in Docker. Connect via VNC at `vnc://localhost:5900` (password: `secret`).

### JavaScript Tests

Frontend tests using Mocha + Chai + Sinon. **Free plugin only.**

- **Location:** `mailpoet/tests/javascript/`
- **File pattern:** `*.spec.ts`
- **Run:** `./do test:javascript`

### Newsletter Editor JavaScript Tests (Legacy)

Legacy Mocha test suite for the newsletter editor. **Do not write new tests here** — only modify existing ones if necessary. **Free plugin only.**

- **Location:** `mailpoet/tests/javascript-newsletter-editor/`
- **Run:** `./do test:newsletter-editor`

### Performance Tests

Load and performance testing with k6 and Playwright. **Free plugin only.**

- **Location:** `mailpoet/tests/performance/`
- **Setup:** `./do test:performance-setup`
- **Run:** `./do test:performance --url=... --us=... --pw=...`
- **Cleanup:** `./do test:performance-clean`

## Quick Reference

| Type              | Command                             | Runs in | Plugin    |
| ----------------- | ----------------------------------- | ------- | --------- |
| Unit              | `./do test:unit`                    | Local   | Free only |
| Integration       | `./do test:integration --skip-deps` | Docker  | Both      |
| Acceptance        | `./do test:acceptance --skip-deps`  | Docker  | Both      |
| JavaScript        | `./do test:javascript`              | Local   | Free only |
| Newsletter Editor | `./do test:newsletter-editor`       | Local   | Free only |
| Performance       | `./do test:performance`             | Docker  | Free only |
