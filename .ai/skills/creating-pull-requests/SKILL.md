---
name: creating-pull-requests
description: 'ALWAYS use when asked to: create a PR, open a PR, make a PR, push and create PR, submit changes for review. Do NOT use gh pr create directly.'
---

# Creating Pull Requests

## ⚠️ CRITICAL

**NEVER run `gh pr create` directly.** ALWAYS follow this skill's workflow.

## Overview

Always create pull requests as **drafts** and follow the repository's PR template format.

## Workflow

1. **Read the PR template first** refer .github/pull_request_template.md
2. **Gather context**
3. **Check for changelog** — if the branch has user-facing changes and no changelog entry exists yet, use the `writing-changelog` skill to create one before proceeding
4. **Create as draft**
5. **Follow template sections exactly** - Not all sections are mandatory, use `_N/A_` for non-applicable ones
6. There are some checkboxes on the bottom of the template, only check the ones that are applicable.

## Code Review Notes Section

The "Code review notes" section is **only for information that helps the reviewer but isn't obvious from the diff**. Think hard before filling it in — leave it as `_N/A_` if you have nothing genuinely useful to say.

**Good code review notes** point the reviewer to:

- Non-obvious trade-offs or decisions ("Used a transient instead of an option because...")
- Risks or areas that need extra scrutiny ("The capability check order matters here because...")
- Context the reviewer might lack ("This mirrors the pattern in `OtherClass::method()` for consistency")
- Things you're unsure about and want a second opinion on

**Bad code review notes** just describe what the code does:

- "Added a new method `doThing()` that does the thing" — the reviewer can see that
- "No changes to `OtherFile.php`" — irrelevant
- Listing method names, parameters, or guard clauses — that's reading the diff aloud

If every bullet could be derived by reading the code, use `_N/A_` instead.

## Common Mistakes

| Mistake                            | Fix                                                                                 |
| ---------------------------------- | ----------------------------------------------------------------------------------- |
| Using `gh pr create` directly      | ALWAYS use this skill workflow first                                                |
| Creating non-draft PR              | Always use `--draft` flag                                                           |
| Custom PR format                   | Read and follow `.github/pull_request_template.md`                                  |
| Missing sections                   | Include all template sections, use `_N/A_` if not applicable                        |
| Skipping template read             | ALWAYS read the template first, it may have changed                                 |
| Narrating the diff in review notes | Only write what the reviewer can't see from the code. Use `_N/A_` if nothing to add |
| Missing changelog entry            | Check for changelog before creating the PR. Use the `writing-changelog` skill       |
