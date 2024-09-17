### Table of Contents

1. [MailPoet](#mailpoet)
2. [Initial setup](#initial-setup)
   1. [Additional dependencies](#additional-dependencies)
3. [Xdebug](#xdebug)
   1. [PhpStorm setup](#phpstorm-setup)
   2. [Xdebug develop mode](#xdebug-develop-mode)
   3. [Xdebug for integration tests](#xdebug-for-integration-tests)
4. [Local development](#local-development)
   1. [NFS volume sharing for Mac](#nfs-volume-sharing-for-mac)
   2. [Husky hooks](#husky-hooks)
5. [Docker](#docker)
   1. [Commands](#commands)
   2. [Available PHP versions](#available-php-versions)
   3. [Disabling the Tracy panel](#disabling-the-tracy-panel)
   4. [Running individual tests](#running-individual-tests)
6. [TODO](#todo)

## MailPoet

The **MailPoet** plugin monorepo.

If you have **any questions or need help or support**, please see the [Support](SUPPORT.md) document.

To use our Docker-based development environment (recommended), continue with the steps below.
If you'd like to use the plugin code directly, see details in [the plugin's readme](mailpoet/README.md).

## Initial setup

1. Run `./do setup` to pull everything and install necessary dependencies.
2. Add secrets to `.env` files in `mailpoet` and `mailpoet-premium` directories. Go to the Secret Store and look for "MailPoet: plugin .env"
3. Run `./do start` to start the stack.
4. Go to http://localhost:8888 to see the dashboard of the dev environment.

### Additional dependencies

Even though it's possible to run everything using Docker, in the development workflow,
it may be faster and more convenient to run some tasks outside the container. Therefore,
the following tools are recommended:

1. **PHP** as per `composer.json` requirements.
2. **Node.js**, as specified by `.nvmrc`. For automatic management use [nvm](https://github.com/nvm-sh/nvm), [FNM](https://github.com/Schniz/fnm), or [Volta](https://github.com/volta-cli/volta).
3. **pnpm**, as specified in `package.json`. For automatic setup enable [Corepack](https://nodejs.org/docs/latest-v17.x/api/corepack.html) using `corepack enable`.

## Xdebug

### PhpStorm setup

In `Languages & Preferences > PHP > Servers` set path mappings:

```shell
wordpress        -> /var/www/html
mailpoet         -> /var/www/html/wp-content/plugins/mailpoet
mailpoet-premium -> /var/www/html/wp-content/plugins/mailpoet-premium
```

For PHP 8 and XDebug 3 we support **browser debugging extension**.
You can choose extension by your browser in [JetBrains documentation](https://www.jetbrains.com/help/phpstorm/browser-debugging-extensions.html).

To use XDebug inside the **cron**, you need to pass a URL argument `&XDEBUG_TRIGGER=yes`
[in the cron request](https://github.com/mailpoet/mailpoet/blob/bf7bd6d2d9090ed6ec7b8b575bb7d6b08e663a52/lib/Cron/CronHelper.php#L155-L166).
Alternatively, you can add `XDEBUG_TRIGGER: yes` to the `wordpress` service in `docker-compose.yml` and restart it (which will run XDebug also for all other requests).

### Xdebug develop mode

[Xdebug develop mode](https://xdebug.org/docs/develop) is disabled by default because it causes performance issues due to conflicts with the DI container.

It can be enabled when needed using the `XDEBUG_MODE` environment variable. For example, it is possible to enable it by adding the following to `docker-compose.override.yml`:

```
environment:
    XDEBUG_MODE: debug, develop
```

### Xdebug for integration tests

- In Languages & Preferences > PHP > Servers create a new sever named `MailPoetTest`, set the host to `localhost` and port to `80` and set following path mappings:

```shell
wordpress        -> /wp-core
mailpoet         -> /wp-core/wp-content/plugins/mailpoet
mailpoet-premium -> /wp-core/wp-content/plugins/mailpoet-premium
mailpoet/vendor/bin/codecept -> /project/vendor/bin/codecept
mailpoet/vendor/bin/wp -> /usr/local/bin/wp
```

- Add `XDEBUG_TRIGGER: 1` environment to `tests_env/docker/docker-compose.yml` -> codeception service to start triggering Xdebug
- Make PHPStorm listen to connections by clicking on the phone icon

## Local development

### NFS volume sharing for Mac

NFS volumes can bring more stability and performance on Docker for Mac. To setup NFS volume sharing run:

```shell
sudo sh dev/mac-nfs-setup.sh
```

Then create a Docker Compose override file with NFS settings and restart containers:

```shell
cp docker-compose.override.macos-sample.yml docker-compose.override.yml

docker compose down -v --remove-orphans
docker compose up -d
```

**NOTE:** If you are on MacOS Catalina or newer, make sure to put the repository
outside your `Documents` folder, otherwise you may run into [file permission issues](https://objekt.click/2019/11/docker-the-problem-with-macos-catalina/).

### Husky hooks

We use [Husky](https://github.com/typicode/husky) to run automated checks in pre-commit hooks.

In case you're using [NVM](https://github.com/nvm-sh/nvm) for Node version management you may
need to create or update your `~/.huskyrc` file with:

```sh
# This loads nvm.sh and sets the correct PATH before running the hooks:
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
```

Without it, you may experience errors in some Git clients.

## Docker

### Commands

The `./do` script define aliases for most of the commands you will need while working on plugins:

```shell
./do setup                           Setup the environment.
./do start                           Start the docker containers.
./do stop                            Stop the docker containers.
./do ssh [--test]                    Run an interactive bash shell inside the plugin directory.
./do run [--test] <command>          Run a custom bash command in the wordpress container.
./do acceptance [--premium]          Run acceptance tests.
./do build [--premium]               Builds a .zip for the plugin.
./do templates                       Generates templates classes and assets.
./do [--test] [--premium] <command>  Run './do <command>' inside the plugin directory.

Options:
   --test     Run the command using the 'test_wordpress' service.
   --premium  Run the command inside the premium plugin.
```

You can access this help in your command line running `./do` without parameters.

### Available PHP versions

To switch the environment to a different PHP version:

1. Check https://github.com/mailpoet/mailpoet/tree/trunk/dev for a list of available PHP versions. Each directory starting with `php` corresponds to a available version.
2. Configure the `wordpress` service in `docker-compose.override.yml` to build from the desired PHP version Dockerfile (replace {PHP_VERSION} with the name of the directory that corresponds to the version that you want to use):

   ```yaml
   wordpress:
     build:
       context: .
       dockerfile: dev/{PHP_VERSION}/Dockerfile
   ```

3. Run `docker compose build wordpress`.
4. Start the stack with `./do start`.

To switch back to the default PHP version remove what was added in 2) and, run `docker compose build wordpress` for application container and `docker compose build test_wordpress` for tests container,
and start the stack using `./do start`.

### Disabling the Tracy panel

To disable the Tracy panel, add the following to `docker-compose.override.yml`:

```yaml
services:
  wordpress:
    environment:
      MAILPOET_DISABLE_TRACY_PANEL: 1
```

### Running individual tests

It's recommended to run tests in Docker. Free plugin tests can be run using --test flag (`./do --test`). However, to run a premium test, you need to ssh into test container (`./do ssh --test`) and run tests there.

#### Integration test in the free plugin

```shell
./do --test test:integration --skip-deps --file=tests/integration/WP/EmojiTest.php
```

#### Acceptance test in the free plugin

```shell
./do --test test:acceptance --skip-deps --file=tests/acceptance/Misc/MailpoetMenuCest.php
```

#### Unit/integration test in the premium plugin

```shell
./do ssh --test # to enter the container
cd ../mailpoet-premium # switch to premium plugin directory
./do test:unit --file=tests/unit/Config/EnvTest.php
```

#### Acceptance test in the premium plugin

```shell
cd ./mailpoet-premium # switch to premium plugin directory on your local machine
./do test:acceptance --skip-deps --file tests/acceptance/PremiumCheckCest.php
```

## TODO

- [ ] Install WooCommerce
- [ ] Install Members
- [ ] Install other useful plugins by default
