---
name: writing-changelog
description: 'Use when adding a changelog entry for a branch. Use after completing work on a feature, fix, or improvement that is user-facing.'
---

# Writing Changelog Entries

## Overview

User-facing changes MUST have a changelog entry. Each entry is a small Markdown file created by the `pnpm changelog:add` command for free-plugin changes. Follow the steps below in order.

## Workflow

### Step 1: Analyze Branch Changes

Compare the current branch against the base branch to understand what changed.

Read the relevant code changes to understand the user-facing impact.

### Step 2: Categorize and Describe

Pick **one** valid type that best describes the change: `Added`, `Improved`, `Fixed`, `Changed`, `Updated`, `Removed`

Write a **short, user-facing description**:

- Write from the user's perspective — what they see or experience
- Avoid technical jargon (no class names, method names, internal details)
- Start with a verb or noun, not "We" or "The plugin"
- No trailing punctuation — the build system adds it
- Keep it to one sentence

**Good examples:**

- `Fix email rendering issue in Outlook`
- `Add ability to filter subscribers by purchase date`
- `Improve performance of subscriber listing page`

**Bad examples:**

- `Refactor SubscriberRepository query method` (technical jargon)
- `Fix bug in NewsletterEntity::getStatus()` (class/method names)
- `Update dependencies.` (trailing punctuation)

### Step 3: Create the Entry

Run the command from the repo root for free-plugin changes:

```bash
pnpm changelog:add --type=<type> --description="<description>"
```

For premium-only changes, use the premium plugin's Robo command directly because there is no root `pnpm` wrapper for it yet:

```bash
cd mailpoet-premium && ./do changelog:add --type=<type> --description="<description>"
```

### When to Skip

Most branches need a changelog. Skip only when changes are:

- Test-only changes
- CI/build configuration changes
- Documentation-only changes

When in doubt, add a changelog entry.
