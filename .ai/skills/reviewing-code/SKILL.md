---
name: reviewing-code
description: Use when reviewing pull requests or local code changes. Use when asked to review a PR, review code, test changes, verify implementation quality, or do a code review. Triggers on "review this PR", "review my changes", "do a code review".
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

1. **Fetch the latest commits** -- `git fetch origin`, then review `git diff origin/trunk...origin/<branch>`. Never review stale code.
2. **Read the PR** using `gh pr view <number> --json title,body,state,baseRefName,headRefName,files,reviews,statusCheckRollup,labels`
3. **Get the full diff** using `gh pr diff <number>`
4. **Check CI status** using `gh pr checks <number>`
5. **Read linked PRs** -- if the Description section links other PRs (for example the matching premium PR), fetch and read those too with `gh pr view`
6. **Read the changed files in full** -- for each significantly changed file, read the complete file (not just the diff) to understand context

### Local Review Mode

1. **Check branch state** using `git status`, `git branch --show-current`
2. **Get the diff** -- run `git fetch origin`, then `git diff origin/trunk...HEAD` for committed changes and `git diff` / `git diff --cached` for uncommitted changes
3. **Read the changed files in full** for context
4. **Ask the user** what they intended to implement (if not already clear)

### Both Modes

1. **Find the Linear ticket** -- look for a Linear ticket ID in:

   - PR description (PR mode)
   - Commit messages (`git log origin/trunk..HEAD --oneline`)
   - Branch name

   If found, fetch the Linear ticket using the Linear MCP tool (`get_issue`). **Always load the ticket’s comments and attachments.** Verify the changes actually address the ticket requirements. If no Linear ticket is found, skip this step but note it in the output.

2. **Identify the change type** -- classify what the diff primarily touches:

   - PHP backend changes
   - React/TypeScript frontend changes
   - SCSS/CSS styling changes
   - Test-only changes
   - Mixed changes

   This classification determines how to tailor the sub-agent prompts in Step 3.

## Step 3: Parallel Sub-Agent Code Review

Launch multiple sub-agents in parallel using the Agent tool. Each agent receives the full diff and relevant context, but has a specific review focus. Determine the exact count of sub-agents based on the complexity and scope of changes.

**IMPORTANT:** Tailor each agent’s prompt based on the change type identified in Step 2. For PHP-heavy changes, emphasize PHP patterns, Doctrine, WordPress conventions. For frontend changes, emphasize React patterns, TypeScript, accessibility. For mixed changes, cover both.

Give each sub-agent the rules and checklists in [review-checklist.md](review-checklist.md), and check the change against `AGENTS.md`: Code rules, Backward compatibility and Boundaries. Always add the Devil’s Advocate sub-agent described there.

## Step 4: Manual Testing

### Decide Whether to Test

Skip manual testing if ALL of the following are true:

- Changes are test-only, documentation-only, or CI/build configuration only
- No user-facing behavior was changed
- No UI components were modified

If user-facing changes exist, proceed with testing.

### Get Testing Steps

- **PR mode:** Extract testing steps from the “Testing instructions” section of the PR description.
- **Local mode:** Create testing steps based on the changes. Describe what to test and expected outcomes.
- If the PR’s Testing instructions say `_N/A_` but there are clearly user-facing changes, flag this as a concern.

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

## Premium

A premium PR lives in the premium repo; run `gh` from `mailpoet-premium/`. Its translation calls use the `mailpoet-premium` text domain. If it depends on a free-plugin change, review both PRs together and check that they can be released together.
