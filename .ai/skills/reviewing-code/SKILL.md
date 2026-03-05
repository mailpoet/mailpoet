---
name: reviewing-code
description: Use when reviewing pull requests or local code changes. Use when asked to review a PR, review code, test changes, verify implementation quality, or do a code review.
---

# Reviewing Code

## Overview

This skill handles two review modes:

| Mode         | Trigger                             | Input                          |
| ------------ | ----------------------------------- | ------------------------------ |
| PR Review    | User provides a GitHub PR URL       | PR diff, description, CI, etc. |
| Local Review | User asks to review current changes | git diff, branch state         |

## Step 1: Determine Review Mode

- If the user provides a GitHub PR URL (e.g. `https://github.com/.../pull/123`) --> **PR Review mode**
- If the user asks to review current branch, uncommitted changes, or staged changes --> **Local Review mode**
- If ambiguous, ask the user which mode they want.

## Step 2: Gather Context

### PR Review Mode

1. **Read the PR** using `gh pr view <number> --json title,body,state,baseRefName,headRefName,files,reviews,statusCheckRollup,labels`
2. **Get the full diff** using `gh pr diff <number>`
3. **Check CI status** using `gh pr checks <number>`
4. **Read linked PRs** -- if the PR description contains links to other PRs (in the "Linked PRs" section), fetch and read those too with `gh pr view`
5. **Read the changed files in full** -- for each significantly changed file, read the complete file (not just the diff) to understand context

### Local Review Mode

1. **Check branch state** using `git status`, `git branch --show-current`
2. **Get the diff** using `git diff trunk...HEAD` for committed changes and `git diff` / `git diff --cached` for uncommitted changes
3. **Read the changed files in full** for context
4. **Ask the user** what they intended to implement (if not already clear)

### Both Modes

5. **Find the Linear ticket** -- look for a Linear ticket ID in:

   - PR description (PR mode)
   - Commit messages (`git log trunk..HEAD --oneline`)
   - Branch name

   If found, fetch the Linear ticket using the Linear MCP tool (`get_issue`). **Always load the ticket's comments and attachments.** Verify the changes actually address the ticket requirements. If no Linear ticket is found, skip this step but note it in the output.

6. **Identify the change type** -- classify what the diff primarily touches:

   - PHP backend changes
   - React/TypeScript frontend changes
   - SCSS/CSS styling changes
   - Test-only changes
   - Mixed changes

   This classification determines how to tailor the sub-agent prompts in Step 3.

## Step 3: Parallel Sub-Agent Code Review

Launch multiple sub-agents in parallel using the Agent tool. Each agent receives the full diff and relevant context, but has a specific review focus. Determine the exact count of sub-agents based on the complexity and scope of changes.

**IMPORTANT:** Tailor each agent's prompt based on the change type identified in Step 2. For PHP-heavy changes, emphasize PHP patterns, Doctrine, WordPress conventions. For frontend changes, emphasize React patterns, TypeScript, accessibility. For mixed changes, cover both.

### What should be verified

- Scope & Completeness - Do the changes fully address the Linear ticket requirements? List any gaps.
- Are there user-facing changes missing a changelog entry? (Check for files in `mailpoet/changelog/` on the branch.)
- Are there missing test files for the changes? (Compare changed source files to test directories.)
- Do the changes follow existing patterns in the codebase?
- **PHP:** Is DI used correctly? Are WordPress functions called via `WP\Functions`? Are entities/repositories following Doctrine patterns?
- **Frontend:** Are components well-structured? Is state management appropriate? Are named exports used?
- **Other file types:** Are they written properly, named according the correct standards?
- Is the code well-organized -- right files, right namespaces, right directories?
- Are there any design concerns?
- Security & Safety - is the code safe to be shipped? Are all user inputs sanitized and validated properly? Are all security standards used?
- Backward Compatibility - if the code is changed what happens to the users who already used the previous versions?
- Is the code using only supported PHP versions?
- API changes: Do changes to the public API break existing integrations?
- Are any WordPress hooks (actions/filters) added, removed, or changed in signature?
- WooCommerce compatibility: If WooCommerce integration code changed, is it guarded with existence checks?
- Test Coverage: Is there a regression test that reproduces the original bug and verifies the fix?
- Is there sufficient test coverage for the new behavior? Are happy paths, error paths, and edge cases covered?
- Are the right test types used? Unit tests vs Integration tests vs Acceptance tests
- Are there tests that test implementation details rather than behavior?
- Do the test properly check the code or blindly verify the mocks?
- Did all the checks in the GitHub Pull Request passed?

### Devil's Advocate – always spin up this sub-agent

- What are the missing pieces? What is the implementation missing? What is the plan overlooking?
- What edge cases are not handled?
- What happens when this code fails? Are errors handled gracefully?
- What assumptions does this code make that might not always hold?
- Could this cause performance issues at scale (large subscriber lists, many newsletters, high-traffic sites)?
- What would a hostile reviewer point out?
- Are there any "it works but..." concerns -- code that is technically correct but fragile, hard to maintain, or surprising?
- If tests were added, are they actually testing the right things? Are there missing test cases?

## Step 4: Manual Testing

### Decide Whether to Test

Skip manual testing if ALL of the following are true:

- Changes are test-only, documentation-only, or CI/build configuration only
- No user-facing behavior was changed
- No UI components were modified

If user-facing changes exist, proceed with testing.

### Get Testing Steps

- **PR mode:** Extract testing steps from the "QA notes" section of the PR description.
- **Local mode:** Create testing steps based on the changes. Describe what to test and expected outcomes.
- If the PR's QA notes say `_N/A_` but there are clearly user-facing changes, flag this as a concern.

### Execute Browser Testing

1. **Check for testing tools** -- verify the Playwright browser tools or Chrome available. If not, inform the user and explain what should they do and how to enable those.
2. **Navigate to the local dev environment** – if there are any issues with the environment fix them
3. **Follow the testing steps** one by one:
   - Navigate to the relevant page
   - Perform the described actions
   - Take a screenshot documenting that the required behaviour has been fixed or improved
   - Verify expected outcomes match actual behavior
4. **Document results** -- for each step, note: pass/fail, screenshot path, and any unexpected behavior.

## Step 5: Output

### PR Review Mode

Present the review as structured output the user can post as PR comments.

Do NOT post the review to GitHub automatically. Print it for the user to review and post themselves.

### Local Review Mode

Ask the user what they want to do with the findings:

- Fix the issues now
- Get a summary to review later
- Proceed to create a PR (invoke the `creating-pull-requests` skill)

## Common Mistakes

| Mistake                                 | Fix                                                                                 |
| --------------------------------------- | ----------------------------------------------------------------------------------- |
| Reviewing only the diff, not full files | Always read the complete changed files for context                                  |
| Skipping the Linear ticket              | The ticket has acceptance criteria that may not be in the PR description            |
| Not loading ticket comments/attachments | Comments and attachments often contain clarifications and design mockups            |
| Generic security review                 | Tailor findings to the actual code -- do not list OWASP items with no code evidence |
| Posting review to GitHub automatically  | Always print for the user to post themselves                                        |
| Skipping linked PRs                     | Linked PRs need to be reviewed together                                             |
| Not reading PR CI status                | CI failures are important context for the review                                    |
| Missing regression test for bug fix     | Bug fixes without a test that reproduces the bug are incomplete -- always flag      |
