---
name: mailpoet-dev-cycle
description: Linting and code quality workflows for MailPoet development (PHP, JS/TS, CSS/SCSS). Use when fixing code style or following the development workflow.
---

# MailPoet Development Cycle

This skill covers linting, code quality, and building assets for MailPoet development. For testing, see the separate `writing-tests` skill.

## Working Directory

This is a monorepo. All plugin-level `./do` commands MUST be run from the correct subdirectory:

- **Free plugin** (default): `mailpoet/`
- **Premium plugin**: `mailpoet-premium/`
- **Repo root** `./do`: Docker wrapper that forwards commands. Use `./do --test` for test commands, `./do --premium` for premium.

Unless you are explicitly working on the premium plugin, always default to the free plugin directory.

## When to Use This Skill

- Before committing code changes
- When running linting or code quality checks
- When setting up the development environment
- When building frontend assets
- When fixing CI failures related to code quality

## Skill Contents

| Document                                           | Purpose                                                                   |
| -------------------------------------------------- | ------------------------------------------------------------------------- |
| [code-quality.md](code-quality.md)                 | JS/TS linting (ESLint), CSS/SCSS linting (Stylelint), Prettier formatting |
| [php-coding-standards.md](php-coding-standards.md) | PHP lint, PHPCS, PHPStan static analysis                                  |

## Quick Reference

All commands below default to the free plugin. Run from the repo root.

```bash
# QA (all checks: PHP lint + PHPCS + ESLint + Stylelint)
cd mailpoet && ./do qa

# PHP only (lint + PHPCS)
cd mailpoet && ./do qa:php

# PHPStan static analysis
cd mailpoet && ./do qa:phpstan

# JS/TS linting (ESLint + TypeScript check)
cd mailpoet && ./do qa:lint-javascript

# CSS/SCSS linting (Stylelint)
cd mailpoet && ./do qa:lint-css

# Prettier check / fix
cd mailpoet && ./do qa:prettier-check
cd mailpoet && ./do qa:prettier-write

# Fix a single file (PHPCS or ESLint based on extension)
cd mailpoet && ./do qa:fix-file path/to/file.php
cd mailpoet && ./do qa:fix-file path/to/file.tsx
```

## Development Workflow

```mermaid
graph TD
    A[Make Changes] --> B[Run Linting]
    B --> C{Linting Passes?}
    C -->|No| D[Fix Issues]
    D --> B
    C -->|Yes| E[Run Tests]
    E --> F{Tests Pass?}
    F -->|No| G[Fix Tests]
    G --> E
    F -->|Yes| H[Run Prettier]
    H --> I{Prettier Clean?}
    I -->|No| J["./do qa:prettier-write"]
    J --> H
    I -->|Yes| K[Commit]
```

## Pre-Commit Checklist

Before committing, run these from the repo root:

- [ ] `cd mailpoet && ./do qa` -- all PHP and frontend QA checks pass
- [ ] `cd mailpoet && ./do qa:prettier-write` -- formatting is clean
- [ ] Run relevant tests (see the `writing-tests` skill for commands)

## Premium Plugin

When working on `mailpoet-premium/`, substitute the directory:

```bash
cd mailpoet-premium && ./do qa
cd mailpoet-premium && ./do qa:phpstan
```

Or use the root wrapper:

```bash
./do --premium qa
```
