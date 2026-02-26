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
3. **Create as draft**
4. **Follow template sections exactly** - Not all sections are mandatory, use `_N/A_` for non-applicable ones
5. There are some checkboxes on the bottom of the template, only check the ones that are applicable.

## Common Mistakes

| Mistake                       | Fix                                                          |
| ----------------------------- | ------------------------------------------------------------ |
| Using `gh pr create` directly | ALWAYS use this skill workflow first                         |
| Creating non-draft PR         | Always use `--draft` flag                                    |
| Custom PR format              | Read and follow `.github/pull_request_template.md`           |
| Missing sections              | Include all template sections, use `_N/A_` if not applicable |
| Skipping template read        | ALWAYS read the template first, it may have changed          |
