# Code Quality Tools

## Overview

MailPoet uses ESLint for JavaScript/TypeScript, Stylelint for SCSS, and Prettier for formatting. All commands below run from `mailpoet/` (the free plugin directory) unless stated otherwise.

## JavaScript / TypeScript Linting (ESLint)

### Configuration

ESLint uses a flat config at `mailpoet/eslint.config.js` which imports shared configs from the `@mailpoet/eslint-config` package (`packages/js/eslint-config/`):

- **ES5 config** -- for legacy `.js` files in `assets/js/src/` and newsletter editor tests
- **ES6 config** -- for `.jsx` files and JS test files
- **TypeScript config** -- for `.ts` and `.tsx` files in `assets/js/src/`

### Running ESLint

```bash
# Run ESLint + TypeScript type checking (from mailpoet/)
cd mailpoet && ./do qa:lint-javascript

# Fix a single JS/TS file (from mailpoet/)
cd mailpoet && ./do qa:fix-file assets/js/src/path/to/file.tsx
```

Under the hood, `qa:lint-javascript` runs `pnpm run check-types && pnpm run lint`, which executes:

- `tsc --noEmit` (TypeScript type checking)
- `eslint --max-warnings 0` across all JS/TS source and test files

### Fixing ESLint Issues

```bash
# Auto-fix a single file
cd mailpoet && ./do qa:fix-file assets/js/src/settings/pages/basics/stats-notifications.tsx

# Or run eslint directly with --fix
cd mailpoet && pnpm eslint --max-warnings 0 --fix assets/js/src/path/to/file.tsx
```

### Disabling ESLint Rules

Avoid `eslint-disable`. When unavoidable, always add a comment explaining why:

```javascript
/* eslint-disable no-new -- this class has a side-effect in the constructor and it's a library's. */
```

For single-line disables:

```javascript
// eslint-disable-next-line @typescript-eslint/no-unsafe-return -- legacy API returns untyped data
return response.data;
```

## CSS / SCSS Linting (Stylelint)

### Configuration

Stylelint config is at `mailpoet/.stylelintrc`. Key rules:

- Uses `stylelint-scss` and `stylelint-order` plugins
- `postcss-scss` custom syntax for SCSS parsing
- **Alphabetical property order** is enforced (`order/properties-alphabetical-order`)
- Nested selectors must not start with `&-` or `&_` (BEM-style nesting is disallowed)

### Running Stylelint

```bash
# Check all SCSS files (from mailpoet/)
cd mailpoet && ./do qa:lint-css

# Fix SCSS files (auto-fix where possible)
cd mailpoet && pnpm run stylelint -- "assets/css/src/**/*.scss"
```

Under the hood, `qa:lint-css` runs `pnpm run stylelint-check -- "assets/css/src/**/*.scss"`.

### Common Stylelint Issues

| Issue                                | Fix                                    |
| ------------------------------------ | -------------------------------------- |
| Properties not in alphabetical order | Reorder properties alphabetically      |
| Nested selector starts with `&-`     | Restructure to avoid BEM-style nesting |
| Duplicate selectors                  | Consolidate duplicate selectors        |
| Zero values with units (`0px`)       | Remove the unit: `0`                   |
| Long hex colors (`#ffffff`)          | Use short form: `#fff`                 |

### SCSS File Naming

- Use `kebab-case` for file names
- Component files are prefixed with underscore: `_new-component.scss`

## Prettier Formatting

### Configuration

Prettier config is at the repo root `.prettierrc`:

```json
{
  "printWidth": 80,
  "singleQuote": true,
  "trailingComma": "all"
}
```

Files excluded from Prettier are listed in `.prettierignore` (vendor, dist, generated files, etc.).

### Running Prettier

```bash
# Check formatting (from mailpoet/)
cd mailpoet && ./do qa:prettier-check

# Auto-fix formatting (from mailpoet/)
cd mailpoet && ./do qa:prettier-write
```

Prettier runs from the repo root internally via `npx prettier`. It applies to JS, TS, JSX, TSX, SCSS, JSON, and other supported file types.

### When to Run Prettier

Always run `./do qa:prettier-write` before committing. CI checks formatting via `./do qa:prettier-check` during the build step.

## Running All Frontend QA Checks

```bash
# ESLint + Stylelint combined (from mailpoet/)
cd mailpoet && ./do qa:frontend-assets

# Everything: PHP lint + PHPCS + ESLint + Stylelint (from mailpoet/)
cd mailpoet && ./do qa
```

## lint-staged (Git Hooks)

Pre-commit hooks are configured via `lint-staged` in `mailpoet/package.json`. They are controlled by environment variables in `mailpoet/.env`:

| Variable                    | Controls                                            |
| --------------------------- | --------------------------------------------------- |
| `MP_GIT_HOOKS_ENABLE`       | Master switch -- must be `true` to enable any hooks |
| `MP_GIT_HOOKS_ESLINT`       | ESLint on staged `.js`, `.jsx`, `.ts`, `.tsx` files |
| `MP_GIT_HOOKS_STYLELINT`    | Stylelint on staged `.scss`, `.css` files           |
| `MP_GIT_HOOKS_PHPLINT`      | PHP lint on staged `.php` files                     |
| `MP_GIT_HOOKS_CODE_SNIFFER` | PHPCS on staged `.php` files                        |
| `MP_GIT_HOOKS_PHPSTAN`      | PHPStan on staged `.php` files                      |

## Premium Plugin

The premium plugin (`mailpoet-premium/`) has the same JS and CSS linting commands. Run from its directory:

```bash
cd mailpoet-premium && ./do qa:lint-javascript
cd mailpoet-premium && ./do qa:lint-css
```

Or via the root wrapper:

```bash
./do --premium qa:lint-javascript
```

## CI Reference

In CircleCI (`.circleci/config.yml`):

- `qa_js` job runs `./do qa:frontend-assets` (ESLint + Stylelint)
- `build` job runs `./do qa:prettier-check` (Prettier formatting check)
