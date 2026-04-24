# Code Quality Tools

## Overview

MailPoet uses ESLint for JavaScript/TypeScript, Stylelint for SCSS, and Prettier for formatting. Use the root `pnpm` wrappers for the common free-plugin checks unless a command is explicitly marked as Robo-only.

## JavaScript / TypeScript Linting (ESLint)

### Configuration

ESLint uses a flat config at `mailpoet/eslint.config.js` which imports shared configs from the `@mailpoet/eslint-config` package (`packages/js/eslint-config/`):

- **ES5 config** -- for legacy `.js` files in `assets/js/src/` and newsletter editor tests
- **ES6 config** -- for `.jsx` files and JS test files
- **TypeScript config** -- for `.ts` and `.tsx` files in `assets/js/src/`

### Running ESLint

```bash
# Run ESLint + TypeScript type checking
pnpm qa:js

# Fix a single JS/TS file (Robo-only)
cd mailpoet && ./do qa:fix-file assets/js/src/path/to/file.tsx
```

Under the hood, `qa:lint-javascript` runs `pnpm run check-types && pnpm run lint`, which executes:

- `tsc --noEmit` (TypeScript type checking)
- `eslint --max-warnings 0` across all JS/TS source and test files

### Fixing ESLint Issues

```bash
# Auto-fix a single file (Robo-only)
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
# Check all SCSS files
pnpm qa:css

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
# Check formatting
pnpm qa:prettier

# Auto-fix formatting
pnpm qa:fix
```

Prettier runs from the repo root internally via `npx prettier`. It applies to JS, TS, JSX, TSX, SCSS, JSON, and other supported file types.

### ⚠️ Docker vs CI Prettier Mismatch

**Never use Docker's `./do run` to run Prettier write/check.** Docker's `npx` can resolve to a different Prettier version than what CI uses, causing the check to silently pass locally but fail in CI.

The project uses **Prettier 2.6.2** with `singleQuote: true`. CI runs `node_modules/.bin/prettier` from the repo root, which enforces single quotes for all string literals in JS/JSX/TS/TSX files.

**Always format changed files using Prettier directly on macOS** (not Docker):

```bash
# From the repo root — format only the files you've changed
npx prettier@2.6.2 --write mailpoet/assets/js/src/path/to/file.jsx
npx prettier@2.6.2 --write mailpoet/assets/css/src/path/to/file.scss

# Verify both files pass
npx prettier@2.6.2 --check mailpoet/assets/js/src/path/to/file.jsx
```

The most common symptom of this mismatch: CI fails with exactly the files you modified, while `./do qa:prettier-check` passes locally. This means Docker's Prettier formatted with double quotes (`"string"`) instead of the required single quotes (`'string'`).

### When to Run Prettier

Always run `pnpm qa:fix` before committing. CI checks formatting via the underlying Robo Prettier check during the build step.

## Running All Frontend QA Checks

```bash
# ESLint + TypeScript type checking
pnpm qa:js

# Stylelint
pnpm qa:css

# Everything: PHP lint + PHPCS + ESLint + Stylelint
pnpm qa
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
cd mailpoet-premium && pnpm qa:js
cd mailpoet-premium && pnpm qa:css
```

## CI Reference

In CircleCI (`.circleci/config.yml`):

- `qa_js` job runs `./do qa:frontend-assets` (ESLint + Stylelint)
- `build` job runs `./do qa:prettier-check` (Prettier formatting check)
