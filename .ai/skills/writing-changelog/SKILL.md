---
name: writing-changelog
description: 'Use when adding a changelog entry for a branch, after finishing a user-facing feature, fix or improvement, or a development-only change. Triggers on "add a changelog", "write the changelog entry".'
---

# Writing Changelog Entries

## Overview

User-facing changes MUST have a changelog entry. Development-only changes get a `Dev` entry, which the release leaves out of the release notes. Each entry is a small Markdown file created by the `pnpm changelog:add` command for free-plugin changes. Follow the steps below in order.

## Workflow

### Step 1: Analyze Branch Changes

Compare the current branch against the base branch to understand what changed.

Read the relevant code changes to understand the user-facing impact.

If customers won’t notice any of it, the branch is development-only: go to [Development-only changes](#development-only-changes).

### Step 2: Categorize and Describe

Pick **one** user-facing type that best describes the change: `Added`, `Improved`, `Fixed`, `Changed`, `Updated`, `Removed`

Write a **short, user-facing description**:

- Write from the user’s perspective — what they see or experience
- Avoid technical jargon (no class names, method names, internal details)
- Start with a capital letter, not “We” or “The plugin”
- Make it read naturally after the type prefix, and don’t repeat the type verb: the release notes print `Fixed: <description>`
- No trailing punctuation — the build system adds it
- Keep it to one sentence

**Good examples:**

- `Email rendering issue in Outlook` (type `Fixed`)
- `Ability to filter subscribers by purchase date` (type `Added`)
- `Performance of subscriber listing page` (type `Improved`)

**Bad examples:**

- `Fix email rendering issue in Outlook` with type `Fixed` (repeats the type verb)
- `Refactor SubscriberRepository query method` (technical jargon)
- `Fix bug in NewsletterEntity::getStatus()` (class/method names)
- `Update dependencies.` (trailing punctuation)

### Step 3: Create the Entry

Run the command from the repo root for free-plugin changes:

```bash
pnpm changelog:add --type=<type> --description="<description>"
```

### Development-only changes

Don’t skip the entry, and don’t write a user-facing one. A branch gets the `Dev` type when it only has:

- Test-only changes
- CI, build, linting or release tooling changes
- Dev dependency updates
- Documentation-only changes

```bash
pnpm changelog:add --type=Dev --description="Faster lint job in CI"
```

The release leaves `Dev` entries out of the release notes, so write the description for developers.

Use `Dev` only when nothing in the branch is user-facing. A dependency that ships in the plugin is `Updated`, not `Dev`. When in doubt, use a user-facing type.

## Premium

Premium changes get their changelog file in the premium repo, with the same types. The root has no wrapper for it, so run the premium script from its directory:

```bash
cd mailpoet-premium && pnpm changelog:add --type=<type> --description="<description>"
```
