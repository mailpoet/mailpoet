# MailPoet

MailPoet done the right way.

# Contents

- [Setup](#setup)
- [Frameworks and libraries](#frameworks-and-libraries)
- [Workflow Commands](#workflow-commands)
- [Coding and Testing](#coding-and-testing)

# Setup

## Requirements
- PHP 7.0
- NodeJS
- WordPress
- Docker & Docker Compose

## Installation
```bash
# go to WP plugins directory
$ cd path_to_wp_directory/wp-content/plugins
# clone this repository
$ git clone https://github.com/mailpoet/mailpoet.git
$ cd mailpoet
# create the .env file
$ cp .env.sample .env
# change the values on .env file
# install all dependencies (PHP and JS)
$ ./do install
# compile JS and CSS files
$ ./do compile:all
```

# Frameworks and libraries

- [Paris ORM](https://github.com/j4mie/paris).
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

# Workflow Commands

```bash
$ ./do install             # install PHP and JS dependencies
$ ./do update              # update PHP and JS dependencies

$ ./do compile:css         # compiles SCSS files into CSS.
$ ./do compile:js          # bundles JS files for the browser.
$ ./do compile:all         # compiles CSS and JS files.

$ ./do watch:css           # watch CSS files for changes and compile them.
$ ./do watch:js            # watch JS files for changes and compile them.
$ ./do watch               # watch CSS and JS files for changes and compile them.

$ ./do test:unit [--file=...] [--debug]
  # runs the PHP unit tests.
  # if --file specified then only tests on that file are executed.
  # if --debug then tests are executed in debugging mode.
$ ./do test:integration [--file=...] [--multisite] [--debug]
  # runs the PHP integration tests.
  # if --file specified then only tests on that file are executed.
  # if --multisite then tests are executed in a multisite wordpress setup.
  # if --debug then tests are executed in debugging mode.
$ ./do test:multisite:integration # alias for ./do test:integration --multisite
$ ./do test:debug:unit            # alias for ./do test:unit --debug
$ ./do test:debug:integration     # alias for ./do test:integration --debug
$ ./do test:failed:unit           # run the last failing unit test.
$ ./do test:failed:integration    # run the last failing integration test.
$ ./do test:coverage              # run tests and output coverage information.
$ ./do test:javascript            # run the JS tests.
$ ./do test:acceptance [--file=...] [--skip-deps]
  # run acceptances tests into a docker environment.
  # if --file given then only tests on that file are executed.
  # if --skip-deps then it skips installation of composer dependencies.
$ ./do test:acceptance:multisite [--file=...] [--skip-deps]
  # same as test:acceptance but runs into a multisite wordpress setup.
$ ./do delete:docker      # stop and remove all running docker containers.

$ ./do qa:lint             # PHP code linter.
$ ./do qa:lint:javascript  # JS code linter.
$ ./do qa:phpstan          # PHP code static analysis using PHPStan.
$ ./do qa                  # PHP and JS linters.

$ ./do changelog:get  [--version-name=...]     # Prints out changelog and release notes for given version or for newest version.
$ ./do changelog:update  [--version-name=...] [--quiet] # Updates changelog in readme.txt for given version or for newest version.

$ ./do container:dump      # Generates DI container cache.
```

# Storybook

We use [Storybook.js](https://storybook.js.org/) to showcase our React components, which can be used throughout the plugin.

## Usage

Currently, we don't have Storybook published publicly, so developers need to run or build it locally.

To run it locally (on `http://localhost:8083`) while watching the changes (recommended when developing new component), run

```bash
./do storybook:watch
```

To build the static version, which can be accessed via browser, run

```bash
./do storybook:build
```

which will create a `storybook-static` folder with all necessary files. Don't forget to rebuild it when new components are added.

## Building new components

- All stories should be located in `_stories` folder inside the component folder they belong to.
- Run `./do storybook:watch` so all changes are automatically reflected in `http://localhost:8083`.
- Examples are available in `assets/js/src/storybook_demo/_stories` folder.

# Coding and Testing

## DI

We use Symfony/dependency-injection container. Container configuration can be found in `libs/DI/ContainerFactory.php`
The container is configured and used with minimum sub-dependencies to keep final package size small.
You can check [the docs](https://symfony.com/doc/3.4/components/dependency_injection.html) to learn more about Symfony Container.

## PHP-Scoper

We use PHP-Scoper package to prevent plugin libraries conflicts in PHP. Two plugins may be using different versions of a library. PHP-Scoper prefix dependencies namespaces and they are then moved into `vendor-prefixed` directory.
Dependencies handled by PHP-Scoper are configured in extra configuration files `prefixer/composer.json` and `prefixer/scoper.inc.php`. Installation and processing is triggered in post scripts of the main `composer.json` file.

## i18n

We use functions `__()`, `_n()` and `_x()` with domain `mailpoet` to translate strings.

**in PHP code**

```php
__('text to translate', 'mailpoet');
_n('single text', 'plural text', $number, 'mailpoet');
_x('text to translate', 'context for translators', 'mailpoet');
```

**in Twig views**

```html
<%= __('text to translate') %>
<%= _n('single text', 'plural text', $number) %>
<%= _x('text to translate', 'context for translators') %>
```

The domain `mailpoet` will be added automatically by the Twig functions.

**in Javascript code**

First add the string to the translations block in the Twig view:

```html
<% block translations %>
  <%= localize({
    'key': __('string to translate'),
    ...
  }) %>
<% endblock %>
```

Then use `MailPoet.I18n.t('key')` to get the translated string on your Javascript code.

## Acceptance testing

We are using Gravity Flow plugin's setup as an example for our acceptance test suite: https://www.stevenhenty.com/learn-acceptance-testing-deeply/

From the article above:

_Windows users only: enable hard drive sharing in the Docker settings._

The browser runs in a docker container. You can use a VNC client to watch the test run, follow instructions in official
repo: https://github.com/SeleniumHQ/docker-selenium
If you’re on a Mac, you can open vnc://localhost:5900 in Safari to watch the tests running in Chrome. If you’re on Windows, you’ll need a VNC client. Password: secret.
