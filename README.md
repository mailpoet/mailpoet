# MailPoet

MailPoet done the right way.

# Contents

- [Setup](#setup)
- [Frameworks and libraries](#frameworks-and-libraries)
- [Files structure](#files-structure)
- [Workflow Commands](#workflow-commands)
- [Coding and Testing](#coding-and-testing)

# Setup

## Requirements
- PHP 5.6+
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
# download composer
$ curl -sS https://getcomposer.org/installer | php
$ chmod +x ./composer.phar
# install PHP dependencies
$ ./composer.phar install
# install all dependencies (PHP and JS)
$ ./do install
# compile JS and CSS files
$ ./do compile:all
```

# Frameworks and libraries

- [Paris ORM](https://github.com/j4mie/paris).
- [Twig](https://twig.symfony.com/) and [Handlebars](https://handlebarsjs.com/) are used for templates rendering.
- [Monolog](https://seldaek.github.io/monolog/) is used for logging.
- [Robo](https://robo.li/) is used to write and run workflow commands.
- [Codeception](https://codeception.com/) is used to write unit and acceptance tests.
- [Docker](https://www.docker.com/), [Docker Compose](https://docs.docker.com/compose/) and [Selenium](https://www.seleniumhq.org/) to run acceptance tests.
- [React](https://reactjs.org/) is used to create most of UIs.
- [Marionette](https://marionettejs.com/) is used to build the newsletters editor.
- [Stylus](http://stylus-lang.com/) is used to write styles.
- [Mocha](https://mochajs.org/), [Chai](https://www.chaijs.com/) and [Sinon](https://sinonjs.org/) are used to write Javascript tests.
- [ESLint](https://eslint.org/) is used to lint JS files.
- [Webpack](https://webpack.js.org/) is used to bundle assets.

# Files structure
```bash
assets/
  css/src/    # CSS source files using Stylus
  fonts/
  img/
  js/src/    # JS source files
codeception.acceptance.yml
codeception.unit.yml
composer.json
composer.lock
CONTRIBUTING.md
do -> vendor/bin/robo
docker-compose.yml
docker-entrypoint.sh
Dockerfile
index.php
lib/         # PHP source files
license.txt
mailpoet-cron.php
mailpoet_initializer.php
mailpoet.php
package.json
package-lock.json
README.md
readme.txt
RoboFile.php   # workflow commands are defined here
tests/
  acceptance/  # acceptance tests using Codeception
  javascript/  # Javascript tests using mocha, chai and sinon
  unit/        # PHP unit tests using Codeception
uninstall.php
views/         # templates using Twig and Handlebars
webpack.config.js
```

# Workflow Commands

```bash
$ ./do install             # install PHP and JS dependencies
$ ./do update              # update PHP and JS dependencies

$ ./do compile:css         # compiles Stylus files into CSS.
$ ./do compile:js          # bundles JS files for the browser.
$ ./do compile:all         # compiles CSS and JS files.

$ ./do watch:css           # watch CSS files for changes and compile them.
$ ./do watch:js            # watch JS files for changes and compile them.
$ ./do watch               # watch CSS and JS files for changes and compile them.

$ ./do test:unit [--file=...] [--multisite] [--debug]
  # runs the PHP unit tests.
  # if --file specified then only tests on that file are executed.
  # if --multisite then unit tests are executed in a multisite wordpress setup.
  # if --debug then tests are executed in debugging mode.
$ ./do test:multisite:unit # alias for ./do test:unit --multisite
$ ./do test:debug          # alias for ./do test:unit --debug
$ ./do test:failed         # run the last failing unit test.
$ ./do test:coverage       # run unit tests and output coverage information.
$ ./do test:javascript     # run the JS tests.
$ ./do test:acceptance [--file=...] [--skip-deps]
  # run acceptances tests into a docker environment.
  # if --file given then only tests on that file are executed.
  # if --skip-deps then it skips installation of composer dependencies.
$ ./do test:acceptance:multisite [--file=...] [--skip-deps]
  # same as test:acceptance but runs into a multisite wordpress setup.
$ ./do delete:docker      # stop and remove all running docker containers.

$ ./do qa:lint             # PHP code linter.
$ ./do qa:lint:javascript  # JS code linter.
$ ./do qa                  # PHP and JS linters.
```

# Coding and Testing

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
