### Table of Contents

1. [MailPoet](#mailpoet)
2. [Initial setup](#initial-setup)
   1. [Required tools](#required-tools)
3. [Commands](#commands)
   1. [Environment lifecycle](#environment-lifecycle)
   2. [Build and watch](#build-and-watch)
   3. [Quality assurance](#quality-assurance)
   4. [Tests](#tests)
   5. [Migrations, templates, wp-cli](#migrations-templates-wp-cli)
4. [Xdebug](#xdebug)
   1. [PhpStorm setup](#phpstorm-setup)
   2. [VS Code setup](#vs-code-setup)
   3. [Xdebug for integration tests](#xdebug-for-integration-tests)
5. [Husky hooks](#husky-hooks)
6. [Code formatting](#code-formatting)

## MailPoet

The **MailPoet** plugin monorepo.

If you have **any questions or need help or support**, please see the [Support](SUPPORT.md) document.

The development environment is built on [`@wordpress/env`](https://www.npmjs.com/package/@wordpress/env) (wp-env) plus [Mailpit](https://mailpit.axllent.org/) for email capture. Continue with the steps below to set it up. If you'd like to use the plugin code directly without wp-env, see [the plugin's readme](mailpoet/README.md).

## Initial setup

1. Run `pnpm bootstrap` to install dependencies, download WooCommerce test plugins, generate the wp-env override, and compile assets.
2. Add secrets to `.env` files in `mailpoet` and `mailpoet-premium` directories. Go to the Secret Store and look for "MailPoet: plugin .env".
3. Run `pnpm env:start` to start wp-env and the Mailpit SMTP catcher.
4. Open the services:
   - **WordPress** — http://localhost:8888 (admin user: `admin` / `password`)
   - **phpMyAdmin** — http://localhost:8081
   - **Mailpit** — http://localhost:8082 (captures all outgoing mail)

> ✉️ **Mail routing is configured automatically.** Every `pnpm env:start` runs `.wp-env/scripts/configure-mailpoet-dev-smtp.php` via `lifecycleScripts.afterStart`, which points MailPoet's mailer at Mailpit (`host.docker.internal:1026`), seeds a default sender, and skips the MailPoet welcome wizard so it can't overwrite the config. Newsletters land in Mailpit without any wp-admin setup. See [doc/mailpit-setup.md](doc/mailpit-setup.md) for details.

### Required tools

- **Docker Desktop** (wp-env and `tests_env/` both use Docker).
- **PHP** as per `composer.json` requirements (used for host-side QA and the prefixer).
- **Node.js** as specified by `.nvmrc`. For automatic management use [nvm](https://github.com/nvm-sh/nvm), [FNM](https://github.com/Schniz/fnm), or [Volta](https://github.com/volta-cli/volta).
- **pnpm** as specified in `package.json`. For automatic setup enable [Corepack](https://nodejs.org/docs/latest-v17.x/api/corepack.html) using `corepack enable`.
- **[GitHub CLI (`gh`)](https://cli.github.com/)** for downloading private WooCommerce test plugins. Run `gh auth login` to authenticate — no personal access token needed. External contributors without access can skip the download step; the bootstrap script treats those failures as non-fatal.

## Commands

All commands below run from the repository root. The same scripts are also exposed inside `mailpoet/` and `mailpoet-premium/` so `pnpm <task>` works from either plugin directory too. `pnpm -w <task>` runs the root-level script from anywhere in the workspace.

### Environment lifecycle

```shell
pnpm env:start      # Start wp-env + Mailpit (boots the dev environment)
pnpm env:stop       # Stop wp-env + Mailpit (state preserved)
pnpm env:destroy    # Stop and delete all wp-env data + Mailpit
pnpm env:restart    # Destroy + start (fresh WordPress install)
pnpm env:debug      # Start with Xdebug enabled on port 9003
pnpm env:logs       # Tail logs from all wp-env containers
pnpm shell          # Bash into the wp-env cli container
pnpm shell:test     # Bash into the tests_env wordpress container
pnpm wp <args>      # Run wp-cli inside the wp-env container
```

### Build and watch

```shell
pnpm compile        # Compile JS + CSS on host
pnpm compile:js     # Compile JS only
pnpm compile:css    # Compile SCSS only
pnpm watch:js       # Rebuild JS on change
pnpm watch:css      # Rebuild SCSS on change
```

### Quality assurance

```shell
pnpm qa             # All QA checks (PHP + JS + CSS + Prettier)
pnpm qa:js          # ESLint + TypeScript type-check
pnpm qa:css         # Stylelint
pnpm qa:php         # PHP CodeSniffer
pnpm qa:phpstan     # PHPStan static analysis
pnpm qa:prettier    # Prettier (check only)
pnpm qa:fix         # Prettier (write) — auto-format all files
```

### Tests

```shell
pnpm test:unit                 # Unit tests (tests_env)
pnpm test:integration          # Integration tests (tests_env)
pnpm test:acceptance           # Acceptance tests with Selenium (tests_env)
pnpm test:javascript           # Mocha JS tests

pnpm test:unit:premium         # Premium unit tests
pnpm test:integration:premium  # Premium integration tests
pnpm test:acceptance:premium   # Premium acceptance tests

pnpm test:install-deps         # Re-install composer deps before testing
                               # (default test:* scripts pass --skip-deps)
```

Pass any plugin-level `./do` flags directly — they forward through pnpm:

```shell
pnpm test:integration --file=tests/integration/WP/EmojiTest.php
pnpm test:acceptance --file=tests/acceptance/Misc/MailpoetMenuCest.php
```

#### Premium tests from inside the container

```shell
pnpm shell:test
cd /wp-core/wp-content/plugins/mailpoet-premium
./do test:unit --file=tests/unit/Config/EnvTest.php
```

### Migrations, templates, wp-cli

These need a running WordPress runtime, so they go through the wp-env `cli` container automatically:

```shell
pnpm migrations:new <db|app>         # Create a new database migration
pnpm migrations:status               # Show migration status
pnpm templates                       # Generate email template classes
pnpm changelog:add --type=<type> --description="..."
```

## Xdebug

Xdebug is off by default (performance). Start the dev environment with Xdebug enabled:

```shell
pnpm env:debug
```

This runs `wp-env start --update --xdebug=develop,debug`. Xdebug listens on port **9003** inside the container.

### PhpStorm setup

1. **Settings → PHP → Servers**, click **+** to add:
   - Name: `wp-env`
   - Host: `localhost`
   - Port: `8888`
   - Debugger: `Xdebug`
   - Check **Use path mappings**
2. Add the mappings:
   ```
   <repo root>/mailpoet          → /var/www/html/wp-content/plugins/mailpoet
   <repo root>/mailpoet-premium  → /var/www/html/wp-content/plugins/mailpoet-premium
   ```
3. Click the phone icon to **Start Listening for PHP Debug Connections**.
4. Trigger Xdebug with the [JetBrains browser extension](https://www.jetbrains.com/help/phpstorm/browser-debugging-extensions.html) or append `?XDEBUG_TRIGGER=1` to the URL.

For debugging cron jobs, pass `&XDEBUG_TRIGGER=yes` in the cron request URL.

### VS Code setup

Install the **PHP Debug** extension and add to `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html/wp-content/plugins/mailpoet": "${workspaceFolder}/mailpoet",
        "/var/www/html/wp-content/plugins/mailpoet-premium": "${workspaceFolder}/mailpoet-premium"
      }
    }
  ]
}
```

### Xdebug for integration tests

Integration tests run in `tests_env/` (not wp-env), so they use a separate server config:

1. **Settings → PHP → Servers**, add a new server `MailPoetTest`, host `localhost`, port `80`, with mappings:

   ```
   wordpress                     → /wp-core
   mailpoet                      → /wp-core/wp-content/plugins/mailpoet
   mailpoet-premium              → /wp-core/wp-content/plugins/mailpoet-premium
   mailpoet/vendor/bin/codecept  → /project/vendor/bin/codecept
   mailpoet/vendor/bin/wp        → /usr/local/bin/wp
   ```

2. Add `XDEBUG_TRIGGER: 1` to the `codeception_*` service in `tests_env/docker/docker-compose.yml` to trigger Xdebug during tests.
3. Click the phone icon in PhpStorm to listen for connections.

## Husky hooks

We use [Husky](https://github.com/typicode/husky) to run automated checks in pre-commit hooks.

If you use [NVM](https://github.com/nvm-sh/nvm) for Node version management you may need to create or update `~/.huskyrc`:

```sh
# Loads nvm.sh and sets the correct PATH before running the hooks:
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
```

Without it, some Git clients fail on hook execution.

## Code formatting

We use [Prettier](https://prettier.io/) for consistent formatting:

```bash
pnpm qa:fix          # Auto-format all files (Prettier write)
pnpm qa:prettier     # Check formatting (read-only)
```
