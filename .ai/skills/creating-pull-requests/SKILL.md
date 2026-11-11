---
name: creating-pull-requests
description: 'Use when asked to create, open or make a PR, push and create a PR, or submit changes for review. Do NOT use gh pr create directly. Triggers on "open a PR", "create a draft PR", "push this and open a PR".'
---

# Creating Pull Requests

## ⚠️ CRITICAL

**NEVER run `gh pr create` directly.** ALWAYS follow this skill’s workflow.

## Overview

Always create pull requests as **drafts** and follow the repository’s PR template format.

## Workflow

1. **Read the PR template first** refer .github/pull_request_template.md
2. **Gather context**
3. **Check for changelog** — if the branch has user-facing changes and no changelog entry exists yet, use the `writing-changelog` skill to create one before proceeding
4. **Run QA before pushing** — CI will fail the PR otherwise. Always run, even when only TS/JS files changed:

   ```bash
   pnpm qa
   ```

   Fix issues with `pnpm format` and `cd mailpoet && ./do qa:fix-file <path>`. See the `code-quality` skill for single checks and what CI runs, and run the tests for the code you touched (`running-tests`). Commit any auto-formatting changes before opening the PR — landing a “fix Prettier” follow-up commit on a fresh PR is wasteful.

5. **Run the HPOS variants CI skips on feature branches.** The HPOS-off and HPOS-sync acceptance and integration jobs run only on `trunk` and `release`, after merge. If your change touches WooCommerce order code (orders, checkout, WooCommerce Subscriptions, purchase emails, revenue tracking, WooCommerce segments), run the affected `@group woo` tests in both modes before opening the PR. List them with `grep -rlw '@group woo' tests/acceptance tests/integration` from `mailpoet/`, and pick the ones that cover the code you changed.

   ```bash
   pnpm test:acceptance --file=tests/acceptance/<Path>Cest.php --disable-hpos
   pnpm test:acceptance --file=tests/acceptance/<Path>Cest.php --enable-hpos-sync
   pnpm test:integration --file=tests/integration/<Path>Test.php --disable-hpos
   pnpm test:integration --file=tests/integration/<Path>Test.php --enable-hpos-sync
   ```

   In the PR’s **Testing instructions**, say which tests ran, or that this did not apply.

6. **Push and create as draft** — `git push -u origin <branch>`, then `gh pr create --draft` with the filled template as the body
7. **Follow template sections exactly** - Not all sections are mandatory, use `_N/A_` for non-applicable ones
8. There are some checkboxes on the bottom of the template, only check the ones that are applicable.

## Code Review Notes Section

The “Code review notes” section is **only for information that helps the reviewer but isn’t obvious from the diff**. Think hard before filling it in — leave it as `_N/A_` if you have nothing genuinely useful to say.

**Good code review notes** point the reviewer to:

- Non-obvious trade-offs or decisions (“Used a transient instead of an option because...”)
- Risks or areas that need extra scrutiny (“The capability check order matters here because...”)
- Context the reviewer might lack ("This mirrors the pattern in `OtherClass::method()` for consistency")
- Things you’re unsure about and want a second opinion on

**Bad code review notes** just describe what the code does:

- "Added a new method `doThing()` that does the thing" — the reviewer can see that
- "No changes to `OtherFile.php`" — irrelevant
- Listing method names, parameters, or guard clauses — that’s reading the diff aloud

If every bullet could be derived by reading the code, use `_N/A_` instead.

## Common Mistakes

| Mistake                            | Fix                                                                                 |
| ---------------------------------- | ----------------------------------------------------------------------------------- |
| Using `gh pr create` directly      | ALWAYS use this skill workflow first                                                |
| Creating non-draft PR              | Always use `--draft` flag                                                           |
| Custom PR format                   | Read and follow `.github/pull_request_template.md`                                  |
| Missing sections                   | Include all template sections, use `_N/A_` if not applicable                        |
| Skipping template read             | ALWAYS read the template first, it may have changed                                 |
| Narrating the diff in review notes | Only write what the reviewer can’t see from the code. Use `_N/A_` if nothing to add |
| Missing changelog entry            | Check for changelog before creating the PR. Use the `writing-changelog` skill       |
| Skipping Prettier / QA before push | Run `pnpm qa` first; CI will fail the PR otherwise                                  |
| Skipping the trunk-only variants   | If the change touches Woo order code, run step 5 before opening                     |

## Premium

A change in `mailpoet-premium/` gets its own branch and PR in the premium repo; run `git` and `gh` from that directory. Run `pnpm qa` there too, and add the changelog file with `pnpm changelog:add` from `mailpoet-premium/`. When a change spans both plugins, link each PR from the other’s Description section.
