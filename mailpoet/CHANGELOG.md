# Changelog System

The `changelog/` directory contains individual changelog entries that are compiled into the final changelog during releases.

## How it works

Create individual changelog files in this directory. Each file represents a single changelog entry.

## File naming convention

Files should be named using the following pattern:

- `YYYY-MM-DD-HH-MM-SS-{type}-{description}.md`

Examples:

- `2024-01-15-14-30-00-fix-undefined-array-key.md`
- `2024-01-15-14-35-00-improve-polylang-support.md`
- `2024-01-15-14-40-00-update-woocommerce-segments.md`

## File format

Each changelog file should contain:

```markdown
# Type: {Added|Improved|Fixed|Changed|Updated|Removed}

# Description

Brief description of the change
```

## Types

- `Added`: New features
- `Improved`: Enhancements to existing features
- `Fixed`: Bug fixes
- `Changed`: Changes to existing functionality
- `Updated`: Updates to dependencies, requirements, etc.
- `Removed`: Removed features or functionality

## Compilation

During the release process, these individual files are compiled into the final changelog format used in `readme.txt` and `changelog.txt`.
