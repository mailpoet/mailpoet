# Review checklist

Used by Step 3 of the `reviewing-code` skill.

## Important rules

- Only report issues you are CERTAIN about after reading the actual code. If you are unsure, say so explicitly rather than presenting speculation as fact.
- Do NOT flag standard WordPress/WooCommerce patterns as issues (e.g. $wpdb->posts interpolation, get_posts() with posts_per_page => -1 for bounded queries).
- Focus on real bugs, logic errors, security vulnerabilities, and missing edge cases — not stylistic preferences.
- If you find no real issues, say “No issues found” — do not invent problems to fill the review.
- Keep it concise. No filler praise or emoji headers.
- Categorize findings as: Bug, Security, Performance, or Suggestion.

## What should be verified

- Scope & Completeness - Do the changes fully address the Linear ticket requirements? List any gaps.
- Is a changelog entry missing? User-facing changes need one, and development-only changes (CI, linting, tests, dev dependencies, docs) get a `Dev` one, which the release notes leave out. (Check for files in `mailpoet/changelog/`, or `mailpoet-premium/changelog/` for Premium, on the branch.)
- Does the changelog entry have the right type? A `Dev` entry on a change customers can notice hides it from them.
- Does each change that makes a decision have a test, and does the PR say why the rest has none? Apply the `writing-tests` skill; a file that only follows its pattern needs no test of its own.
- Do the changes follow existing patterns in the codebase?
- **PHP:** Is DI used correctly? Are WordPress functions called via `WP\Functions`? Are entities/repositories following Doctrine patterns?
- **Frontend:** Are components well-structured? Is state management appropriate? Are named exports used?
- **Other file types:** Are they written properly, named according the correct standards?
- Is the code well-organized -- right files, right namespaces, right directories?
- Are there any design concerns?
- Scalability: will any new or changed query stay fast at scale (large subscriber, sending, and statistics tables)? Watch for non-sargable `LIKE '%term%'`, JOINs added to listing/count queries, N+1 loops, unbounded scans, and missing indexes -- see the `sql-performance` skill.
- Security & Safety - is the code safe to be shipped? Are all user inputs sanitized and validated properly? Are all security standards used?
- Backward Compatibility - if the code is changed what happens to the users who already used the previous versions?
- Is the code using only supported PHP versions?
- API changes: Do changes to the public API break existing integrations?
- Are any WordPress hooks (actions/filters) added, removed, or changed in signature?
- WooCommerce compatibility: If WooCommerce integration code changed, is it guarded with existence checks?
- Test Coverage: Is there a regression test that reproduces the original bug and verifies the fix?
- Is the happy path covered, plus the one to three high-impact failure cases? Flag edge-case bulk and near-duplicate tests as well as gaps.
- Do the tests pass the `writing-tests` review (section “Reviewing tests you didn’t write”): lowest suite that can catch the failure, a nameable regression per test, values asserted rather than mocks?
- Did all the checks in the GitHub Pull Request pass?
- Can a reviewer follow the PR’s Testing instructions and see the change work? Is every template section filled or `_N/A_`?
- Does a change to a Backward compatibility surface (see `AGENTS.md`) state its BC impact in the Description?

## Devil’s Advocate – always spin up this sub-agent

- What are the missing pieces? What is the implementation missing? What is the plan overlooking?
- What edge cases are not handled?
- What happens when this code fails? Are errors handled gracefully?
- What assumptions does this code make that might not always hold?
- Could this cause performance issues at scale (large subscriber lists, many newsletters, high-traffic sites)?
- What would a hostile reviewer point out?
- Are there any “it works but...” concerns -- code that is technically correct but fragile, hard to maintain, or surprising?
- If tests were added, are they actually testing the right things? Are there missing test cases?
