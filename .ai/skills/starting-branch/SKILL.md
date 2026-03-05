---
name: starting-branch
description: ALWAYS use when creating a new branch, starting work on a task, or working on a Linear issue. Handles branch naming, Linear lookup, and branch creation. Do NOT run git switch -c or git checkout -b directly.
---

# Starting a Branch

## Overview

Creates a properly named Git branch for a Linear issue or task, following the company branch naming conventions.

## Step 1: Determine the Branch Name

### With a Linear Issue

If the user provides a Linear issue ID (e.g. `STOMAIL-1234`):

1. **Fetch the issue** using the Linear MCP tool (`get_issue`) to get the title and type
2. **Pick the prefix** based on issue type: `add/`, `update/`, `fix/` or any other relevant prefix
3. **Generate the branch name:** `<prefix><ISSUE-ID>-<short-description>`
   - Issue ID in the branch name is **lowercase** (e.g. `stomail-1234`)
   - Description is **kebab-case**, derived from the issue title
   - Keep it short but descriptive
   - Example: `fix/stomail-7875-mailpoet-subscription-form-block-reloads-site-editor-iframe`

### Without a Linear Issue

If no Linear issue is provided:
1. Suggest a branch name based on what the user described
2. Ask the user to confirm or provide their own name

### Always Confirm

**Always** present the generated branch name to the user and ask for confirmation before creating it.

## Step 2: Create the Branch

Once confirmed, ensure you are on `trunk` and up to date, then create the branch:

```bash
git switch trunk
git pull
git switch -c <branch-name>
```
