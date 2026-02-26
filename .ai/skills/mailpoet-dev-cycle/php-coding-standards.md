# PHP Coding Standards

## Overview

MailPoet enforces PHP quality through three tools: **PHP lint** (syntax checking), **PHPCS** (coding standards), and **PHPStan** (static analysis). All commands below run from `mailpoet/` unless stated otherwise.

## PHP Lint (Syntax Check)

```bash
# Check PHP syntax (from mailpoet/)
cd mailpoet && ./do qa:lint
```

Runs `parallel-lint lib/ tests/ mailpoet.php`. Fast check that catches syntax errors before running the full PHPCS suite.

## PHP CodeSniffer (PHPCS)

### Running PHPCS

```bash
# PHP lint + PHPCS combined (from mailpoet/)
cd mailpoet && ./do qa:php

# PHPCS only on all files (from mailpoet/)
cd mailpoet && ./do qa:code-sniffer

# PHPCS on specific files (from mailpoet/)
cd mailpoet && ./do qa:code-sniffer lib/Subscribers/SubscribersRepository.php

# Auto-fix a single PHP file with PHPCBF (from mailpoet/)
cd mailpoet && ./do qa:fix-file lib/Subscribers/SubscribersRepository.php
```

### Configuration

PHPCS configuration lives in `mailpoet/tasks/code_sniffer/MailPoet/`:

| File                  | Purpose                                                                             |
| --------------------- | ----------------------------------------------------------------------------------- |
| `shared-ruleset.xml`  | Common rules for both free and premium plugins                                      |
| `free-ruleset.xml`    | Free plugin -- references shared-ruleset, sets text domain to `mailpoet`            |
| `premium-ruleset.xml` | Premium plugin -- references shared-ruleset, sets text domain to `mailpoet-premium` |
| `php-version.xml`     | Sets minimum PHP version to 7.4                                                     |

### Base Standards

The shared ruleset extends:

- **WordPress-Extra** -- WordPress coding standards (with many rules disabled, see below)
- **WordPress-VIP-Go** -- VIP Go standards (for non-test/non-tool code)
- **PHPCompatibility** -- ensures compatibility with PHP 7.4+
- **SlevomatCodingStandard** -- additional strict rules for namespaces, type hints, etc.

### MailPoet Naming Conventions

MailPoet uses PSR-style naming, which differs from WordPress defaults:

| Element                | Convention         | Example                          |
| ---------------------- | ------------------ | -------------------------------- |
| Classes                | `CamelCase`        | `SubscribersRepository`          |
| Methods                | `camelCase`        | `findByEmail()`                  |
| Variables / Properties | `camelCase`        | `$subscriberCount`, `$firstName` |
| Constants              | `UPPER_SNAKE_CASE` | `MAX_BATCH_SIZE`                 |

WordPress `snake_case` variable naming rules are disabled in the shared ruleset. Instead, MailPoet enforces `camelCase` for variables and properties via the Squiz `ValidVariableName` sniffs (`MemberNotCamelCaps`, `StringNotCamelCaps`).

### Indentation

MailPoet uses **2-space indentation** (tabs are disallowed):

```php
class SubscribersRepository {
  public function findByEmail(
    string $email
  ): ?SubscriberEntity {
    return $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->findOneBy(['email' => $email]);
  }
}
```

### Key Enforced Rules

- **Short array syntax** required (`[]` not `array()`)
- **Strict types** declaration enforced: `declare(strict_types = 1);`
- **Alphabetically sorted** `use` statements
- **No unused** `use` statements
- **Trailing commas** in multi-line arrays
- **No assignments** in conditions
- **Visibility** required on all methods
- **camelCase** variable and property names (enforced via Squiz `ValidVariableName` sniffs)
- **One class per file**, class name must match file name
- **Constructor parameters** always on multiple lines
- **No space after NOT** operator (`!$condition`, not `! $condition`)

### Disabled Rules (Legacy Debt)

Many WordPress coding standard rules are disabled via `<severity>0</severity>` in the shared ruleset. These represent legacy code patterns that don't match WordPress conventions. Key disabled categories:

- WordPress whitespace/spacing rules (MailPoet uses PSR-style spacing)
- WordPress naming conventions (MailPoet uses camelCase)
- Yoda conditions (`WordPress.PHP.YodaConditions`)
- Various WordPress-VIP-Go restrictions around direct DB queries, file operations, etc.

New code should still follow the enabled rules strictly. Do not re-enable disabled rules without coordinating with the team.

### Understanding PHPCS Output

```text
FILE: lib/Subscribers/SubscribersRepository.php
----------------------------------------------------------------------
FOUND 2 ERRORS AFFECTING 2 LINES
----------------------------------------------------------------------
 12 | ERROR | [x] Expected 1 blank line after the last use statement;
    |       |     0 found (SlevomatCodingStandard.Namespaces.UseSpacing)
 25 | ERROR | [ ] Variable "$orderID" is not in valid camelCase format
    |       |     (Squiz.NamingConventions.ValidVariableName.StringNotCamelCaps)
----------------------------------------------------------------------
```

- `[x]` -- auto-fixable with `./do qa:fix-file`
- `[ ]` -- requires manual fix
- The sniff code in parentheses identifies the exact rule

### Disabling PHPCS Rules Inline

When you must disable a rule, always explain why:

```php
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in parent method
$value = sanitize_text_field(wp_unslash($_POST['field']));
```

The one exception: `Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps` does not require an explanation (used when accessing WordPress/WooCommerce properties that use snake_case).

```php
// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
$post_title = $post->post_title;
```

For multi-line disables:

```php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- output is pre-escaped HTML from renderer
echo $rendered_html;
// phpcs:enable
```

## PHPStan (Static Analysis)

### Running PHPStan

```bash
# Run PHPStan (from mailpoet/)
cd mailpoet && ./do qa:phpstan

# Run with specific PHP version target
cd mailpoet && ./do qa:phpstan --php-version=70400
cd mailpoet && ./do qa:phpstan --php-version=80300
```

### Configuration

PHPStan config is at `mailpoet/tasks/phpstan/phpstan.neon`:

- **Level 9** (strictest)
- Includes Doctrine ORM, WordPress, and PHPUnit extensions
- Bootstraps the full vendor autoload plus WooCommerce stubs
- Scans `lib/`, `tests/` directories
- Excludes `generated/`, template files, and legacy model tests

### Key PHPStan Behaviors

- Type annotations on all `Automation\` namespace code are required (other namespaces have relaxed type requirements due to legacy)
- Doctrine entity relationships are understood via the `phpstan-doctrine` extension
- WordPress functions are typed via `phpstan-wordpress`
- Baseline errors are managed -- do not add new baseline entries without discussion

## Running All PHP QA

```bash
# Full PHP QA: lint + PHPCS (from mailpoet/)
cd mailpoet && ./do qa:php

# Full QA: PHP + frontend (from mailpoet/)
cd mailpoet && ./do qa
```

## Premium Plugin

The premium plugin uses the same shared ruleset but with its own text domain. Run from `mailpoet-premium/`:

```bash
# PHPCS (uses premium-ruleset.xml automatically)
cd mailpoet-premium && ./do qa:code-sniffer

# Full QA (lint + PHPCS + ESLint + Stylelint)
cd mailpoet-premium && ./do qa

# PHPStan (reuses free plugin's phpstan binary)
cd mailpoet-premium && ./do qa:phpstan

# Fix a single file
cd mailpoet-premium && ./do qa:fix-file lib/SomePremiumClass.php
```

Key difference: text domain must be `mailpoet-premium` (not `mailpoet`) in translation functions.

## CI Reference

In CircleCI (`.circleci/config.yml`):

| Job                      | What it runs                                             | PHP version      |
| ------------------------ | -------------------------------------------------------- | ---------------- |
| `qa_php`                 | `./do qa:php` (lint + PHPCS)                             | Latest (8.3)     |
| `qa_php_oldest`          | `./do qa:php`                                            | Oldest (7.4)     |
| `qa_php_max_wporg`       | `./do qa:php-max-wporg` (lint including build artifacts) | 8.1 (WP.org max) |
| `static_analysis` (php7) | `./do qa:phpstan --php-version=70400`                    | Latest           |
| `static_analysis` (php8) | `./do qa:phpstan --php-version=80300`                    | Latest           |
| `security_analysis`      | `./do qa:semgrep`                                        | 8.1              |

## Quick Command Reference

```bash
# PHP syntax check
cd mailpoet && ./do qa:lint

# PHPCS check
cd mailpoet && ./do qa:code-sniffer

# PHP lint + PHPCS
cd mailpoet && ./do qa:php

# PHPStan
cd mailpoet && ./do qa:phpstan

# Auto-fix a file
cd mailpoet && ./do qa:fix-file path/to/file.php

# Full QA (PHP + JS + CSS)
cd mailpoet && ./do qa
```
