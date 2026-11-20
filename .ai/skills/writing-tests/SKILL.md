---
name: writing-tests
description: 'Use before writing, planning or reviewing tests for MailPoet — deciding whether a change needs a test at all, which suite it belongs in (unit, integration, acceptance, JavaScript), how to write it, and how to prove it catches a real regression. Triggers on "write a test", "add tests for this", "which suite should this test go in", "review these tests". For invoking the test runner see running-tests. For investigating a CI failure see debugging-failed-tests.'
---

# Writing Tests

Tests cost time three times: to write, on every CI run, and every time someone debugs a red build. Write the tests that catch a real regression, at the cheapest level that can catch it, and no others. A test earns its place when it catches a break no other test catches.

**Lint the pattern, test the decision.** Most files are one of a few kinds (REST or JSON API endpoint, repository, automation step, form handler, React form, settings field, email block…) and follow the codebase’s pattern for that kind. A lint or static-analysis rule checks the pattern in every file, including files not written yet. A test is for the part of a file that goes beyond its pattern. Full coverage is not the goal.

For running tests see [[running-tests]]. Commands come from the root `package.json`; don’t guess them.

## 1. Does this change need a test?

Ask: **if someone breaks this next month, what goes wrong, and would anyone notice before a customer does?**

- **Yes, test it:** logic that makes a decision (permissions, limits, prices, dates, sending, segments, billing, data shown to the wrong person), a bug that has happened before, and every security fix.
- **Usually no new test:**
  - Copy, styling, layout and markup changes. Check them in the browser.
  - Config, version bumps, dependency updates, renames and pure refactors. The existing tests must pass before and after.
  - Code that only passes values through to WordPress, WooCommerce or a library with no decision of its own. Don’t test the framework.
  - A small, low-risk fix whose only possible test would be an acceptance test. Check it by hand instead.
- **Already covered?** Search the existing tests first. Add an assertion to an existing test or a case to a data provider before writing a new test.

**Name the regression before you keep a test.** Write one sentence: a realistic code change that would make this test fail and that no existing test already catches. “If the segment filter stopped excluding unsubscribed subscribers, they would get the next newsletter” is a sentence. “Verifies the class works” is not. No sentence, no test.

**What to cover:** the happy path, plus usually one to three high-impact cases. A case is high impact when it is a failure that loses data, money, access or security; the bug being fixed; or the main error result a caller acts on. Skip boundary, empty-input and odd-format variants unless one of them is the bug.

**A file that only follows its pattern gets no test of its own.** Such a test restates the code and never finds a bug. A file earns a test when it decides something the pattern doesn’t:

- it branches on a type, scope, role or status;
- it builds a write from a read, and a failed read must stop the write;
- it updates only some fields (which ones reach the write?);
- it does more than one write (order, and what happens when one fails);
- it makes a security decision, such as a value the server decides instead of taking it from the request;
- it remaps or swallows an error, or reshapes a payload;
- a form whose fields depend on each other or whose input is reshaped before submit; a list or dropdown that filters, groups or maps its options.

Plain utility functions have no pattern: test them directly. “Add tests for the endpoints” means test the endpoints that decide something, not one test per file.

When you decide not to write a test, say so in the PR in one line, with the reason. “No test: copy change, checked in the browser” is a complete answer.

## 2. Which suite?

Pick the **lowest** level that can catch the failure. Go one level up only when the lower one can’t, and write down why.

| You need to test…                                                         | Use                           | Why                                                                           |
| ------------------------------------------------------------------------- | ----------------------------- | ----------------------------------------------------------------------------- |
| PHP logic with no WP / DB dependency (WP calls mocked via `WP\Functions`) | Unit                          | Fastest, no Docker, runs on the host                                          |
| A SQL query, migration, Doctrine repository, or real WP wiring            | Integration                   | Real WordPress + DB inside Docker                                             |
| A critical browser flow nothing lower can protect                         | Acceptance                    | Only place to drive an actual browser                                         |
| Frontend React/TS logic                                                   | JavaScript                    | Mocha-based, host-side                                                        |
| The legacy Backbone newsletter editor                                     | _do not write new tests here_ | The Backbone editor is being replaced — extend the block-based editor instead |
| Load / scale behaviour                                                    | Performance                   | k6 + Playwright; only for perf work                                           |

- **Unit is the default.** Most logic can be tested here by calling the class directly with plain inputs. If the code is hard to unit test because it reads the database or globals in the middle of its logic, a small refactor that moves the decision into a plain method is often the better fix.
- **Integration** only when the behaviour _is_ the database or the real wiring. Integration tests are slower and need the Docker test stack, so keep them few and focused.
- **Acceptance almost never.** Slow, flaky and slow to debug. Add one only for a critical user flow (sending an email, sign-up, checkout opt-in) when the task asks for it, never for a bug a unit test can catch. If an acceptance test already walks the flow, add an assertion to it instead of a new test.

### Pattern checks belong in a linter, not a test

If a test would only check that a file follows its pattern (the capability check runs first, the WordPress call goes through `WP\Functions`, output is escaped), that check belongs in PHPStan, phpcs (MailPoet’s own sniffs are in `mailpoet/tasks/code_sniffer/MailPoet/`) or ESLint. A rule catches a dropped line in every file, with `file:line`, before review.

- If the rule exists, rely on it and don’t repeat it in a test.
- If it doesn’t, say in the PR which rule would replace the test, instead of writing the pattern test.

## 3. Where new tests go and what they extend

### PHP unit (`mailpoet/tests/unit/`)

- File pattern `*Test.php`, base class `MailPoetUnitTest`.
- **Free plugin only** — premium has no unit suite. If your test needs to live in `mailpoet-premium/`, it has to be an integration test.
- No WordPress runtime, no DB. Mock the `WP\Functions` wrapper for any WP call (which is why production code must go through that wrapper — see AGENTS.md).

### PHP integration (`mailpoet/tests/integration/` and `mailpoet-premium/tests/integration/`)

- File pattern `*Test.php`, base class `\MailPoetTest` (extends Codeception’s WP integration).
- WordPress and DB are live — write tests against the real Doctrine repositories where you can. Mock the seams that aren’t under test.
- Multisite-specific behaviour: there’s a multisite mode (see `running-tests` for how to invoke it); when authoring, guard multisite-only code paths so non-multisite runs of the same file still pass.

### PHP acceptance (`mailpoet/tests/acceptance/` and `mailpoet-premium/tests/acceptance/`)

- File pattern `*Cest.php`. Each test method takes the `AcceptanceTester $i` parameter.
- Use `$i->wantTo("describe the scenario")` at the top of each method instead of comments — it lands in the run output and is the only documentation a future debugger sees.
- Anchor on `data-automation-id` / ARIA selectors, not DOM structure or 3rd-party markup. If a component has no stable hook, add a `data-automation-id` in the production code.
- Always wait explicitly for the element you’re about to interact with (`waitForElement`, `waitForText`, …), and ask what could make the test fail intermittently.

### JavaScript (`mailpoet/tests/javascript/`)

- File pattern `*.spec.ts`. Mocha + Chai + Sinon.
- **Free plugin only.**
- Test logic, not the DOM.

### Test data builders

Reusable fixtures go in `tests/DataFactories/`. If you are building the same entity shape a second time, add it to a factory.

## 4. How to write it

- **Small, linear tests with descriptive names.** No `if` / `for` / `while` in a test: loops hide which case failed. The name says the scenario and the expected outcome, so CI output alone tells a reader what broke.
- **Test behaviour through the public interface.** No reflection into private or protected methods.
- **Assert values, not shapes.** `verify($result)->same('jane@example.com')`, not `->notEmpty()` or `->isString()`.
- **`same()` over `equals()`** in new assertions (`verify()->same()`, `assertSame()`), unless you compare objects by value. Don’t rewrite existing assertions only to change this.
- **Write the expected value as a literal.** Don’t recompute it with the helpers or defaults the code under test uses, or the test agrees with the bug.
- **Mocks only at the edges** (`WP\Functions`, HTTP, time, mail, the MailPoet sending service, WooCommerce). A test that only checks that your mocks were called proves the mocks work, not the code. Assert what leaves the code instead: the rows written (and that none are written after a failure), the returned result, and side effects such as scheduled actions, emails or redirects.
- **Stubs must not hide calls.** `Stub::makeEmpty()` returns an empty value (`null`, `false`, `[]`, `''`, by return type) for every method the test didn’t set up, so an unexpected call passes silently and can send the code down another branch. When a call must not happen, say so with `Expected::never()` or `->expects($this->never())`.
- **Don’t copy mock setup between test files.** A setup you need a second time belongs in a factory or a shared helper (`tests/DataFactories/`, `tests/_support/Helper/`). Set up the happy path once in `_before()` / `beforeEach`, then each test changes only what it is about.
- **Check behaviour that goes through another plugin once against the real plugin.** A stub of WooCommerce Bookings, Subscriptions or Memberships can drift from the real one: a Premium test once passed only against its own Bookings stub and called a method the real plugin doesn’t have. When a change alters what a user or merchant sees through another plugin, run it at least once with the real plugin installed (an integration test or a browser check) and say which version in the PR.
- **No hollow tests:** no `assertTrue(true)`, no `addToAssertionCount(1)`, no “the class exists”, no “does not throw” with nothing else checked, no test that pins a constant to its own literal (`verify(Foo::KEY)->same('key')`).
- **No near-duplicates.** Three tests that differ in one input are one test with a data provider. Keep providers short (about 3 to 6 cases, one per distinct outcome). New providers can be `public static`: PHPUnit 9.6 accepts both and PHPUnit 10 requires it.
- **Pin broad exceptions.** A bare `expectException(\Exception::class)` passes when the wrong path throws. Expect the specific exception class, or add `expectExceptionCode()`. Use `expectExceptionMessage()` only when nothing else tells the paths apart, and match a short stable part: long message strings change.
- **Don’t assert inside a callback the code under test may catch.** Filters, hooks and stub callbacks often run inside a production `try`/`catch`, which swallows the failed assertion. Record what the callback received, then assert after the call returns.
- **Fast and isolated.** No sleeps, no real time, no dependence on test order or on state left by another test method.
- **Prefer self-explanatory tests over comments.** Rename or split the test first; keep a comment only if it still adds something.
- **Only the tests your change adds or changes.** Don’t rewrite existing tests that are out of scope.

## 5. Prove it

- **Red before green.** Run each new test before the fix and watch it fail for the reason it exists (the assertion about the bug, not a syntax error or a missing fixture). Then fix, and watch it pass. Never commit a test you haven’t seen pass; if you can’t run it, say so.
- **Break what it protects.** Delete the guard, flip the condition or return early, confirm the test fails, then restore the file and confirm it passes. If it still passes with the guard removed, tighten the assertion or drop the test. This is required for security fixes.
- **Run the cheap tests often** (unit, and the tests you touched). Run integration or acceptance suites at the end, one suite at a time: they share the test database.

## 6. Reviewing tests you didn’t write

Apply sections 1, 2 and 4 to the tests a PR adds or changes, and report findings instead of editing: tests with no nameable regression, edge-case bulk, near-duplicates, hollow or constant-pinning tests, computed expected values, broad exceptions, assertions inside caught callbacks, mocks beyond the edges, pattern checks that belong in a linter, and a suite higher than needed. Don’t flag tests the PR didn’t touch.

## 7. Report

In the PR, under “Code review notes”, a reviewer should see the test decision, not guess it:

```
Tests
Kept:
- FooTest::testBar (unit): catches <realistic change that would fail it>; fails when <the line you broke>
Merged or dropped:
- testX, testY → merged into the testZ data provider (near-duplicates)
Not tested:
- <what, and the one-line reason, e.g. "copy change, checked in the browser">
```

Before reporting, compare the number of new tests with the lines of production code changed. Many tests for a small change usually means one change tested many times: look again.

## Related skills

- [[running-tests]] — how to invoke the test runner (single file, whole suite, premium variants, debug mode, multisite, Docker shell).
- [[debugging-failed-tests]] — investigating a CI failure and shipping a fix.
- [[code-quality]] — the broader lint/format/test loop you should run before pushing.
