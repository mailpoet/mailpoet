# Running Tests

## DATABASE SAFETY WARNING

Unit and integration tests can **wipe the database** of the current WordPress development instance. Always run tests from the **repo root** using `./do --test` to target the isolated test Docker service with a separate database.

**Never** run codecept directly outside Docker for tests that touch the database.

## Test Types

| Type                 | File Pattern | Location                              | Framework                      |
| -------------------- | ------------ | ------------------------------------- | ------------------------------ |
| Unit                 | `*Test.php`  | `tests/unit/`                         | Codeception (PHPUnit)          |
| Integration          | `*Test.php`  | `tests/integration/`                  | Codeception (Docker)           |
| Acceptance           | `*Cest.php`  | `tests/acceptance/`                   | Codeception (Selenium, Docker) |
| JavaScript           | `*.spec.ts`  | `tests/javascript/`                   | Mocha                          |
| Newsletter Editor JS | `*.js`       | `tests/javascript-newsletter-editor/` | Mocha                          |
| Performance          | `*.js`       | `tests/performance/`                  | k6                             |

## Unit Tests

Run from the **repo root** to use the isolated test database:

```bash
# Run a single test file
./do --test test:unit --file=tests/unit/WooCommerce/CouponPreProcessorTest.php

# Run a single test file with debug output
./do --test test:unit --debug --file=tests/unit/WooCommerce/CouponPreProcessorTest.php

# Run all unit tests
./do --test test:unit
```

### Options

| Option          | Description                      |
| --------------- | -------------------------------- |
| `--file=<path>` | Run only the specified test file |
| `--debug`       | Enable debug output              |
| `--xml`         | Generate XML test report         |

### Guidelines

- Unit tests should be fast and isolated with no database or WordPress dependency
- Place test helpers in `tests/_support/`
- Place reusable test data builders in `tests/DataFactories/`

## Integration Tests

Run from the **repo root**. Integration tests run inside Docker containers via `tests_env/docker/docker-compose.yml` with their own MySQL and WordPress instance.

```bash
# Run a single test file
./do --test test:integration --skip-deps --file=tests/integration/Segments/WPTest.php

# Run all integration tests
./do --test test:integration --skip-deps
```

### Options

| Option                | Description                                                     |
| --------------------- | --------------------------------------------------------------- |
| `--file=<path>`       | Run only the specified test file                                |
| `--skip-deps`         | Skip dependency reinstallation (recommended during development) |
| `--group=<name>`      | Run only tests in the specified group (e.g., `woo`)             |
| `--skip-group=<name>` | Skip tests in the specified group                               |
| `--enable-hpos`       | Enable WooCommerce HPOS (High-Performance Order Storage)        |
| `--enable-hpos-sync`  | Enable HPOS with sync                                           |
| `--disable-hpos`      | Disable HPOS                                                    |
| `--skip-plugins`      | Skip loading additional plugins                                 |
| `--stop-on-fail`      | Stop on first failure                                           |
| `--debug`             | Enable debug output                                             |
| `--xml`               | Generate XML test report                                        |
| `--multisite`         | Run as WordPress multisite                                      |

### Specialized Integration Test Commands

```bash
# WooCommerce-specific integration tests
cd mailpoet && ./do test:woo-integration

# Base integration tests (skips WooCommerce group)
cd mailpoet && ./do test:base-integration

# Multisite integration tests
cd mailpoet && ./do test:multisite-integration --skip-deps --file=tests/integration/Path/To/Test.php
```

## Acceptance Tests

Run from the **repo root**. Acceptance tests use browser automation (Selenium/Codeception) inside Docker containers.

```bash
# Run a single test file
./do --test test:acceptance --skip-deps --file=tests/acceptance/EmailEditor/EmailTemplatesCest.php

# Run all acceptance tests
./do --test test:acceptance --skip-deps
```

### Multisite Acceptance Tests

```bash
./do --test test:acceptance-multisite --skip-deps --file=tests/acceptance/Newsletters/ReceiveScheduledEmailCest.php
```

### Options

| Option                | Description                                                     |
| --------------------- | --------------------------------------------------------------- |
| `--file=<path>`       | Run only the specified test file                                |
| `--skip-deps`         | Skip dependency reinstallation (recommended during development) |
| `--group=<name>`      | Run only tests in the specified group                           |
| `--enable-hpos`       | Enable WooCommerce HPOS                                         |
| `--enable-hpos-sync`  | Enable HPOS with sync                                           |
| `--disable-hpos`      | Disable HPOS                                                    |
| `--timeout=<seconds>` | Set wait timeout                                                |
| `--skip-plugins`      | Skip loading additional plugins                                 |

### Acceptance Test Groups

Tests are organized into groups via annotations:

- `@group woo` -- WooCommerce-related tests
- `@group frontend` -- Frontend/theme-related tests

## JavaScript Tests

JavaScript tests use Mocha and do **not** touch the database. Run from `mailpoet/`:

```bash
# Run all JS tests
cd mailpoet && ./do test:javascript

# Run legacy newsletter editor JS tests
cd mailpoet && ./do test:newsletter-editor
```

## Performance Tests

Performance tests use k6 and Docker. Run from `mailpoet/`:

```bash
# Run a specific performance test
cd mailpoet && ./do test:performance tests/performance/tests/newsletter-listing.js

# With options
cd mailpoet && ./do test:performance --url=http://localhost:9500 --pw=password --scenario=pullrequests
```

See `mailpoet/tests/performance/README.md` for full setup instructions and available scenarios.

## Docker Test Infrastructure

Test containers are defined in `tests_env/docker/docker-compose.yml`:

- **`codeception_integration`** -- runs integration tests with its own MySQL
- **`codeception_acceptance`** -- runs acceptance tests with Selenium and its own WordPress
- **`wordpress` (test)** -- isolated WordPress instance for test container

### Useful Docker Commands

```bash
# SSH into the test WordPress container
./do ssh --test

# SSH into the test container for the premium plugin
./do ssh --test --premium

# Run an arbitrary command in the test container
./do run --test "wp option get siteurl"

# DESTRUCTIVE -- requires explicit user confirmation before running
# Reset test Docker containers and volumes
cd mailpoet && ./do reset-test-docker

# DESTRUCTIVE -- requires explicit user confirmation before running
# Delete all test Docker resources (removes images too)
cd mailpoet && ./do delete-docker
```

**WARNING**: `reset-test-docker` and `delete-docker` are destructive commands that destroy Docker containers, volumes, and (for delete) images. **Always ask the user for explicit confirmation before running these commands.**

## Premium Plugin Tests

The premium plugin has `test:integration` and `test:acceptance` commands. It does not have a standalone `test:unit` command (use `test:debug` for unit test debugging).

```bash
# SSH into test container and run premium tests
./do ssh --test --premium
./do test:integration --skip-deps --file=tests/integration/Path/To/Test.php

# Or use the root wrapper
./do --premium test:integration --skip-deps --file=tests/integration/Path/To/Test.php
```

Premium acceptance tests:

```bash
./do ssh --test --premium
./do test:acceptance --skip-deps --file=tests/acceptance/Path/To/Cest.php
```

## Test Data Factories

Reusable test data builders live in `tests/DataFactories/`. Use them to create test entities:

```php
$subscriber = (new \MailPoet\Test\DataFactories\Subscriber())
  ->withEmail('test@example.com')
  ->withStatus('subscribed')
  ->create();
```

## Debugging Tests

### PHPUnit / Codeception

```bash
# Debug output for unit tests
./do --test test:unit --debug --file=tests/unit/Path/To/SomeTest.php

# Debug integration tests
./do --test test:integration --skip-deps --debug --file=tests/integration/Path/To/SomeTest.php

# Run previously failed tests
cd mailpoet && ./do test:failed-unit
cd mailpoet && ./do test:failed-integration

# Interactive debugging: SSH into test container
./do ssh --test
# Then run codecept directly with breakpoints
../tests_env/vendor/bin/codecept run unit --debug -f tests/unit/Path/To/SomeTest.php
```

### Acceptance Tests

```bash
# Test output and screenshots are saved to:
# tests/_output/
# tests/_output/exceptions/

# View artifacts after a test run
ls mailpoet/tests/_output/
```

## CI Reference

In CircleCI (`.circleci/config.yml`):

| Job                 | Description                                                       |
| ------------------- | ----------------------------------------------------------------- |
| `unit_tests`        | Runs `./do t:u --xml` (unit tests)                                |
| `integration_tests` | Multiple configurations with different HPOS/multisite/WC settings |
| `acceptance_tests`  | Parallelized (20 containers), various configurations              |
| `js_tests`          | Newsletter editor + JS tests                                      |
| `performance_tests` | k6 performance tests (nightly only)                               |

Integration and acceptance tests in CI run with various matrix configurations:

- WooCommerce versions (latest, oldest, beta)
- WordPress versions (latest, oldest, beta)
- HPOS on/off/sync
- Multisite on/off
- Block-based theme on/off

## Quick Command Reference

```bash
# Unit tests (from repo root)
./do --test test:unit --file=tests/unit/Path/To/SomeTest.php

# Integration tests (from repo root)
./do --test test:integration --skip-deps --file=tests/integration/Path/To/SomeTest.php

# Acceptance tests (from repo root)
./do --test test:acceptance --skip-deps --file=tests/acceptance/Path/To/SomeCest.php

# Multisite acceptance (from repo root)
./do --test test:acceptance-multisite --skip-deps --file=tests/acceptance/Path/To/SomeCest.php

# JS tests (from mailpoet/)
cd mailpoet && ./do test:javascript

# Performance tests (from mailpoet/)
cd mailpoet && ./do test:performance tests/performance/tests/newsletter-listing.js

# SSH into test container
./do ssh --test
```
