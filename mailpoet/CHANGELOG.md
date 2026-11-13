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
# Type: {Added|Improved|Fixed|Changed|Updated|Removed|Dev}

# Description

Brief description of the change
```

Descriptions should not end with punctuation. The release compiler adds semicolons between entries and a period after the final entry.
Descriptions should also start with a capital letter and read naturally after the generated type prefix. Avoid repeating the type verb, for example use `Generated WooCommerce coupon block support for regular newsletters and automation emails` instead of `Added generated WooCommerce coupon block support for regular newsletters and automation emails`.

## Types

- `Added`: New features
- `Improved`: Enhancements to existing features
- `Fixed`: Bug fixes
- `Changed`: Changes to existing functionality
- `Updated`: Updates to dependencies, requirements, etc.
- `Removed`: Removed features or functionality
- `Dev`: Development-only changes that customers won’t notice, such as CI, linting, tests, build and release tooling, dev dependencies and documentation

`Dev` entries stay out of the release notes. Use `Dev` only when nothing in the change is user-facing. A dependency that ships in the plugin is `Updated`, not `Dev`.

## Compilation

During the release process, these individual files are compiled into the final changelog format used in `readme.txt` and `changelog.txt`. `Dev` entries are left out. A release with only `Dev` entries gets the line `Improved: minor changes and fixes`. All entry files, `Dev` ones too, are deleted once the changelog is compiled.
