---
name: creating-pull-requests
description: Use when asked to create a pull request, open a PR, or submit changes for review
---

# Creating Pull Requests

## Overview

Always create pull requests as **drafts** and follow the repository's PR template format.

## Workflow

1. **Read the PR template first** - Check `.github/pull_request_template.md`
2. **Create as draft** - Use `gh pr create --draft`
3. **Follow template sections exactly** - Fill in all sections, use `_N/A_` for non-applicable ones

## Quick Reference

```bash
# Read template
cat .github/pull_request_template.md

# Create draft PR with template format
gh pr create --draft --title "Short title" --body "$(cat <<'EOF'
## Description
...
## Code review notes
...
EOF
)"
```

## Common Mistakes

| Mistake               | Fix                                                          |
| --------------------- | ------------------------------------------------------------ |
| Creating non-draft PR | Always use `--draft` flag                                    |
| Custom PR format      | Read and follow `.github/pull_request_template.md`           |
| Missing sections      | Include all template sections, use `_N/A_` if not applicable |
