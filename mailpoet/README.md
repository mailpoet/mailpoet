### Table of Contents

1. [MailPoet](#mailpoet)
2. [Setup](#setup)
   1. [Requirements](#requirements)
   2. [Installation](#installation)
   3. [Frameworks and libraries](#frameworks-and-libraries)
3. [Workflow Commands](#workflow-commands)
   1. [Sample Data Generator](#sample-data-generator)
4. [Coding and Testing](#coding-and-testing)
   1. [DI](#di)
   2. [PHP-Scoper](#php-scoper)
   3. [Changelog](#changelog)
   4. [i18n](#i18n)
   5. [Acceptance testing](#acceptance-testing)

## MailPoet

- For help with product, visit [SUPPORT](../SUPPORT.md).
- For the wp-env-based development environment (recommended), see the [monorepo README](../README.md).
- To use the plugin code directly against your own WordPress install, follow the instructions below.

## Setup

### Requirements

- PHP >= 7.4
- NodeJS
- WordPress

### Installation

The instructions below assume you already have a working WordPress development environment:

```bash
# 1. Clone this repository somewhere outside the WordPress installation:
git clone https://github.com/mailpoet/mailpoet.git

# 2. Go to the plugin directory within cloned the repository:
cd mailpoet/mailpoet

# 3. Symlink the MailPoet plugin to your WordPress installation:
ln -s $(pwd) <wordpress>/wp-content/plugins/mailpoet

# 4. Create the .env file:
cp .env.sample .env

# 5. Install dependencies (PHP and JS):
pnpm install

# 6. Compile JS and CSS:
pnpm compile
```

### Frameworks and libraries

- [Symfony/dependency-injection](https://github.com/symfony/dependency-injection) ([docs for 3.4](https://symfony.com/doc/3.4/components/dependency_injection.html)).
- [PHP-Scoper](https://github.com/humbug/php-scoper) for moving dependencies into MP namespace
- [Twig](https://twig.symfony.com/) and [Handlebars](https://handlebarsjs.com/) are used for templates rendering.
- [Monolog](https://seldaek.github.io/monolog/) is used for logging.
- [Robo](https://robo.li/) is used to write and run workflow commands.
- [Codeception](https://codeception.com/) is used to write unit and acceptance tests.
- [Docker](https://www.docker.com/), [Docker Compose](https://docs.docker.com/compose/) and [Selenium](https://www.seleniumhq.org/) to run acceptance tests.
- [React](https://reactjs.org/) is used to create most of UIs.
- [Marionette](https://marionettejs.com/) is used to build the newsletters editor.
- [SCSS](http://sass-lang.com/) is used to write styles.
- [Mocha](https://mochajs.org/), [Chai](https://www.chaijs.com/) and [Sinon](https://sinonjs.org/) are used to write Javascript tests.
- [ESLint](https://eslint.org/) is used to lint JS files.
- [Webpack](https://webpack.js.org/) is used to bundle assets.

## Workflow Commands

Use `pnpm` scripts for the common workflow commands. They wrap the plugin-level Robo tasks where needed and can be run from the repo root or from this plugin directory.

At the repo root there are also `pnpm` scripts (`pnpm env:start`, `pnpm test:integration`, `pnpm migrations:*`, …) that orchestrate the wp-env container and route WordPress-runtime tasks into it. See the [monorepo README](../README.md) for the full list.

Use `./do` directly only for Robo helpers that do not have a `pnpm` wrapper yet. Those are listed separately below.

**Rule of thumb:**

- **On host (via `pnpm`)**: compile, watch, lint, PHPStan, PHPCS, Prettier, JS tests (mocha)
- **In wp-env container (via `pnpm`)**: migrations, templates, wp-cli commands, anything needing WordPress runtime
- **In `tests_env/` container (via `pnpm test:*`)**: integration, unit, acceptance tests

```bash
$ pnpm install             # install PHP and JS dependencies

$ pnpm compile:css         # compiles SCSS files into CSS.
$ pnpm compile:js          # bundles JS files for the browser.
$ pnpm compile             # compiles CSS and JS files.

$ pnpm watch:css           # watch CSS files for changes and compile them.
$ pnpm watch:js            # watch JS files for changes and compile them.

$ pnpm test:unit [--file=...] [--debug]
  # runs the PHP unit tests.
  # if --file specified then only tests on that file are executed.
  # if --debug then tests are executed in debugging mode.
$ pnpm test:integration [--file=...] [--multisite] [--debug]
  # runs the PHP integration tests.
  # if --file specified then only tests on that file are executed.
  # if --multisite then tests are executed in a multisite wordpress setup.
  # if --debug then tests are executed in debugging mode.
$ pnpm test:javascript            # run the JS tests.
$ pnpm test:acceptance [--file=...]
  # run acceptances tests into a docker environment.
  # if --file given then only tests on that file are executed.
  # the pnpm wrapper passes --skip-deps by default.

$ pnpm qa:php              # PHP lint + PHPCS.
$ pnpm qa:js               # JS code linter + TypeScript check.
$ pnpm qa:css              # CSS code linter.
$ pnpm qa:phpstan          # PHP code static analysis using PHPStan.
$ pnpm qa:prettier         # Prettier formatting check.
$ pnpm qa:fix              # Prettier formatting write.
$ pnpm qa                  # PHP and JS linters.

$ pnpm changelog:add --type=<type> --description=<description> # Creates a new changelog entry
```

Robo-only helpers with no `pnpm` wrapper yet:

```bash
$ ./do update              # update PHP and JS dependencies
$ ./do test:failed-unit           # run the last failing unit test.
$ ./do test:failed-integration    # run the last failing integration test.
$ ./do test:acceptance-multisite [--file=...] [--skip-deps]
  # same as test:acceptance but runs into a multisite wordpress setup.
$ ./do download:woo-commerce-zip [<tag>]
$ ./do download:woo-commerce-subscriptions-zip [<tag>]
  # download 3rd party plugins for tests
  # if you pass tag it will attempt to download zip for the tag otherwise it downloads the latest release
  # e.g. ./do download:woo-commerce-zip 5.20.0
$ ./do delete:docker      # stop and remove all running docker containers.

$ ./do qa:lint             # PHP syntax linter only.
$ ./do qa:code-sniffer     # PHPCS only.
$ ./do qa:fix-file <path>  # Auto-fix one PHP or JS/TS file.
$ ./do release:changelog-get [--version-name=...] # Prints changelog and release notes.
$ ./do release:changelog-update [--version-name=...] [--quiet] # Updates changelog in readme.txt.
$ ./do changelog:preview [--version=<version>] # Preview compiled changelog for next version.
$ ./do container:dump      # Generates DI container cache.
```

### Sample Data Generator

`pnpm generate:sample-data` seeds the local environment with a realistic MailPoet dataset: lists, subscribers, dynamic segments, draft and sent newsletters, post notifications, welcome emails, automations, opens/clicks, and — when WooCommerce is active — products and orders linked back to revenue stats. The generator needs a running WordPress instance, so it runs inside the `wp-env` container; start the env first with `pnpm env:start`.

The script is available both at the repo root and inside `mailpoet/` — pick whichever is convenient. Pick a preset and override individual knobs:

```bash
pnpm generate:sample-data                                  # default preset, single thread
pnpm generate:sample-data --preset=small                   # small smoke dataset
pnpm generate:sample-data 4 --preset=large                 # 4 parallel workers, large dataset
pnpm generate:sample-data --subscribers=1000 --purchase-rate=0.2
pnpm generate:sample-data --woocommerce=0                  # skip WooCommerce data
```

The optional `<threads>` positional spawns parallel worker processes (each runs an independent dataset).

| Option                 | Default       | Description                                              |
| ---------------------- | ------------- | -------------------------------------------------------- |
| `--preset`             | `default`     | One of `default`, `small`, `large`                       |
| `--lists`              | `5`           | Number of static subscriber lists                        |
| `--dynamic-segments`   | `3`           | Number of dynamic segments                               |
| `--subscribers`        | `500`         | Total subscribers distributed across lists               |
| `--products`           | `10`          | WooCommerce products to create (requires WooCommerce)    |
| `--draft-newsletters`  | `5`           | Draft newsletters                                        |
| `--sent-newsletters`   | `30`          | Sent standard newsletters                                |
| `--post-notifications` | `6`           | Post notification history items                          |
| `--automatic-emails`   | `5`           | Legacy WooCommerce-driven automatic emails               |
| `--automations`        | `3`           | Automations created                                      |
| `--automation-runs`    | `75`          | Automation runs distributed across automations           |
| `--days-min`           | `1`           | Minimum days in the past for backdated activity          |
| `--days-max`           | `180`         | Maximum days in the past for backdated activity          |
| `--open-rate`          | `0.35`        | Fraction of recipients that open (0.0–1.0)               |
| `--click-rate`         | `0.20`        | Fraction of openers that click (0.0–1.0)                 |
| `--purchase-rate`      | `0.30`        | Fraction of clickers that purchase (0.0–1.0)             |
| `--orders-min`         | `1`           | Minimum orders per buying subscriber                     |
| `--orders-max`         | `3`           | Maximum orders per buying subscriber                     |
| `--email-domain`       | `example.com` | Domain used for generated subscriber emails              |
| `--prefix`             | `Sample data` | Prefix prepended to generated names/subjects             |
| `--woocommerce`        | `1`           | Set to `0` to skip WooCommerce products, orders, revenue |
| `--welcome-emails`     | `1`           | Set to `0` to skip welcome email generation              |

Presets `small` and `large` only override volume-related options (lists, subscribers, newsletters, automations, etc.); rates, domain, and prefix come from the `default` baseline. Any explicit `--option=value` always wins over the preset.

The defaults live in `tests/DataGenerator/Generators/SampleDataConfig.php` (`CLI_OPTIONS_DEFAULTS` and the `PRESET_*` constants).

> Note: `./do generate:data` exists too but only works inside the wp-env container (it needs the WordPress runtime). The `pnpm` script above is the supported entry point — it routes the same command into the container for you.

## Coding and Testing

### DI

We use Symfony/dependency-injection container. Container configuration can be found in `lib/DI/ContainerFactory.php`
The container is configured and used with minimum sub-dependencies to keep final package size small.
You can check [the docs](https://symfony.com/doc/3.4/components/dependency_injection.html) to learn more about Symfony Container.

### PHP-Scoper

We use PHP-Scoper package to prevent plugin libraries conflicts in PHP. Two plugins may be using different versions of a library. PHP-Scoper prefix dependencies namespaces and they are then moved into `vendor-prefixed` directory.
Dependencies handled by PHP-Scoper are configured in extra configuration files `prefixer/composer.json` and `prefixer/scoper.inc.php`. Installation and processing is triggered in post scripts of the main `composer.json` file.

### Changelog

Create changelog entries using:

```bash
pnpm changelog:add --type=Fixed --description="Brief description of the change"
```

See [readme](changelog/README.md) for detailed documentation.

### i18n

We use functions `__()`, `_n()`, `_x()`, and `_nx()` with domain `mailpoet` to translate strings. Please follow [best practices](https://codex.wordpress.org/I18n_for_WordPress_Developers).

#### Comments for translators

When the translation string can be ambiguous, add [a translators comment](https://codex.wordpress.org/I18n_for_WordPress_Developers#Descriptions) for clarification. Don't use `_x()` or `_xn()` for clarification.

```php
// translators:
$customErrorMessage = sprintf(
  // translators: %1$s is the link, %2$s is the error message.
  __('Please see %1$s for more information. %2$s.', 'mailpoet'),
  'https://kb.mailpoet.com',
  $errorMessage
);
```

#### In PHP code

```php
__('text to translate', 'mailpoet');
_n('single text', 'plural text', $number, 'mailpoet');
_x('text to translate', 'context', 'mailpoet');
_xn('single text', 'plural text', $number, 'context', 'mailpoet');
```

#### In JavaScript/TypeScript code

```ts
import { __, _n, _x, _xn } from '@wordpress/i18n';

__('text to translate', 'mailpoet');
_n('single text', 'plural text', number, 'mailpoet');
_x('text to translate', 'context', 'mailpoet');
_nx('single text', 'plural text', number, 'context', 'mailpoet');
```

To replace placeholders in translated strings, use `sprintf`:

```ts
import { sprintf } from '@wordpress/i18n';

sprintf(__('Hello %s', 'mailpoet'), 'John');
```

To replace React elements use `createInterpolateElement`:

```tsx
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CustomComponent } from '../custom-component.js';

const translatedString = createInterpolateElement(
  __(
    'This is a <span>string</span> with a <a>link</a> and a self-closing <custom_component/>.',
  ),
  {
    span: <span class="special-text" />,
    a: <a href="https://make.wordpress.org" />,
    custom_component: <CustomComponent />,
  },
);
```

For more information, see the [@wordpress/i18n](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/)
and the [createInterpolateElement](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/#createinterpolateelement)
guides.

### Acceptance testing

To run the whole acceptance testing suite you need the docker daemon to be running and after that use a command: `pnpm test:acceptance`.
If you want to run only a single test use the parameter `--file`:

```bash
pnpm test:acceptance --file=tests/acceptance/ReceiveStandardEmailCest.php
```

The `pnpm` wrapper passes `--skip-deps` by default to speed up the run locally.

If there are some unexpected errors you can delete all the runtime and start again.
To delete all the docker runtime for acceptance tests use the Robo-only command `cd mailpoet && ./do delete:docker`.

We are using Gravity Flow plugin's setup as an example for our acceptance test suite: https://www.stevenhenty.com/learn-acceptance-testing-deeply/

From the article above:

_Windows users only: enable hard drive sharing in the Docker settings._

The browser runs in a docker container. You can use a VNC client to watch the test run, follow instructions in official
repo: https://github.com/SeleniumHQ/docker-selenium
If you’re on a Mac, you can open vnc://localhost:5900 in Safari to watch the tests running in Chrome. If you’re on Windows, you’ll need a VNC client. Password: secret.
