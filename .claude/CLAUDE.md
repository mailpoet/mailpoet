# MailPoet Plugin - Project Overview

## About MailPoet

**MailPoet** is a WordPress email marketing plugin that allows users to create, send, and manage newsletters and automated emails directly from their WordPress dashboard. The plugin integrates deeply with WordPress and WooCommerce to provide comprehensive email marketing capabilities.

This is a **monorepo** containing two plugins:

- **mailpoet** - The free version of the plugin. The free plugin follows a traditional WordPress plugin structure with modern PHP architecture.
- **mailpoet-premium** - The premium (paid) version with additional features. The premium plugin extends the free version with additional features.

## Project Structure

```text
/mailpoet
├── mailpoet/                    # Free plugin
├── mailpoet-premium/            # Premium plugin
├── wordpress/                   # WordPress installation for development
├── tests_env/                   # Test environment
├── dev/                         # Docker development configuration
├── doc/                         # Documentation
├── packages/                    # Shared packages
├── templates/                   # Email templates
├── do                           # Main CLI script for development commands
├── docker-compose.yml           # Docker configuration
└── README.md                    # Main documentation
```

## Key Technologies

- **PHP 7.4+** - Main programming language
- **Doctrine ORM** - Database abstraction and entity management
- **WordPress** - Plugin platform
- **React** - Frontend UI (admin pages, email editor)
- **TypeScript** - Frontend type safety
- **Docker** - Development environment
- **Codeception** - Testing framework
- **Action Scheduler** - Background job processing (from WooCommerce)
- **PHPStan** - Static analysis
- **pnpm** - Package manager

## Development Workflow

### Essential Commands

```bash
./do test:acceptance --skip-deps --file tests/acceptance/Misc/WordPressSiteEditorCest.php # Run an end to end test
```

### Plugin-Specific Commands

When inside a plugin directory (`mailpoet/` or `mailpoet-premium/`):

```bash
./do test:unit --file tests/unit/WooCommerce/TransactionalEmails/FontFamilyValidatorTest.php
./do test:integration --skip-deps --file  tests/integration/Logging/LogHandlerTest.php
./do qa:php # run PHP CodeSniffer to ensure all PHP files follow coding standards
./do qa:phpstan # run PHPStan static analysis
./do qa:prettier-write # check and update JavaScript/TypeScript formatting
./do compile:all # compile javascript and css changes for testing
./do compile:js # compile javascript changes for testing
composer install                # Install PHP dependencies
pnpm install                    # Install JavaScript dependencies
```

## Testing

- **Unit Tests** - Fast, isolated tests using Codeception
- **Integration Tests** - Tests with WordPress/database using Codeception
- **Acceptance Tests** - Browser-based E2E tests using Selenium/Codeception

Test files are located in:

- `mailpoet/tests/`
- `mailpoet-premium/tests/`

## Database

The plugin uses **Doctrine ORM** for database management:

- **Entities** - PHP classes representing database tables (in `lib/Entities/`)
- **Repositories** - Database access classes (e.g., `SubscribersRepository`)
- **Migrations** - Database schema changes (in `lib/Migrations/`)

## Important Patterns

### Dependency Injection

The plugin uses a PSR-11 container for dependency injection:

- Container configuration: `mailpoet/lib/DI/`
- Services are auto-wired based on type hints

# Commits

Make sure each commit addresses an atomic unit of work that independently works.

Make sure the commit message has a subject line which includes a brief description of the change and (if needed), why it was necessary. Write the subject in the imperative, start with a verb, and do not end with a period. The subject line should be no longer than 50 characters.

There must be an empty line between the subject line and the rest of the commit message (if any). The commit body should be no longer than 72 characters.

The commit message should explain what caused the problem and what its consequences are (if helpful for understanding the changes).

The commit message should explain how the changes achieve the goal, but only if it isn't obvious.

# Code Quality

This rule provides guidance for best code quality practices and preventing bugs.

## Best Practices

Don't trust that extension developers will follow the best practices, make sure that all code:

- Guards against unexpected inputs.
- Sanitizes and validates any potentially dangerous inputs.
- Is backwards compatible.
- Is readable and intuitive.
- Has unit or E2E tests where applicable.

### WordPress Hooks

Integration with WordPress uses a wrapper class:

- `mailpoet/lib/WP/Functions.php` - Wrapper for WordPress functions
- Makes testing easier by allowing mocking

## Additional Resources

- Main README: `README.md`
- Free plugin README: `mailpoet/README.md`
- Premium plugin README: `mailpoet-premium/README.md`
- Contributing Guide: `CONTRIBUTING.md`
