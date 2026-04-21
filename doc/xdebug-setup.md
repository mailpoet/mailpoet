# Xdebug Setup

Xdebug is off by default (wp-env doesn't enable it in normal operation). Start the dev environment with Xdebug on:

```bash
pnpm env:debug
```

That's equivalent to `wp-env start --update --xdebug=develop,debug`. Xdebug listens on port **9003** inside the container and connects back to the host via `host.docker.internal`.

---

## PhpStorm

1. **Settings → PHP → Servers** → click **+** to add a new server:
   - **Name:** `wp-env`
   - **Host:** `localhost`
   - **Port:** `8888` (or whatever you set in `.wp-env.json` / your `.wp-env.override.json`)
   - **Debugger:** `Xdebug`
   - Check **Use path mappings**
2. Add mappings (replace `<repo>` with the absolute path to this repo):
   | Host path | Server path |
   |---------------------------------|--------------------------------------------------------------|
   | `<repo>/mailpoet` | `/var/www/html/wp-content/plugins/mailpoet` |
   | `<repo>/mailpoet-premium` | `/var/www/html/wp-content/plugins/mailpoet-premium` |
3. Click the phone icon (top-right) → **Start Listening for PHP Debug Connections**.
4. Trigger Xdebug one of these ways:
   - Install the [JetBrains browser extension](https://www.jetbrains.com/help/phpstorm/browser-debugging-extensions.html) and enable it for `localhost:8888`.
   - Append `?XDEBUG_TRIGGER=1` (or `&XDEBUG_TRIGGER=1`) to the URL you load.
   - Set an `XDEBUG_TRIGGER` cookie.

For debugging cron jobs that MailPoet dispatches internally, pass `&XDEBUG_TRIGGER=yes` in the cron request URL — see [`CronHelper.php`](../mailpoet/lib/Cron/CronHelper.php).

---

## VS Code

Install the **PHP Debug** extension (`xdebug.php-debug`). Add to `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug (wp-env)",
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

Select **Run and Debug → Listen for Xdebug (wp-env)**. Trigger via `?XDEBUG_TRIGGER=1` or the VS Code browser extension.

---

## Integration tests (`tests_env/`)

Integration tests run in a separate docker-compose stack, not wp-env. You need a different PhpStorm server:

1. **Settings → PHP → Servers** → add `MailPoetTest`:
   - **Host:** `localhost`
   - **Port:** `80`
2. Mappings:
   | Host path | Server path |
   |--------------------------------------|------------------------------------------------------|
   | `wordpress` | `/wp-core` |
   | `mailpoet` | `/wp-core/wp-content/plugins/mailpoet` |
   | `mailpoet-premium` | `/wp-core/wp-content/plugins/mailpoet-premium` |
   | `mailpoet/vendor/bin/codecept` | `/project/vendor/bin/codecept` |
   | `mailpoet/vendor/bin/wp` | `/usr/local/bin/wp` |
3. In `tests_env/docker/docker-compose.yml`, add `XDEBUG_TRIGGER: 1` to the `codeception_integration` service's `environment:` block to trigger Xdebug automatically when tests run.
4. Click the phone icon to listen. Run `pnpm test:integration --file=...` — breakpoints inside test code or plugin code should hit.

---

## Troubleshooting

**Breakpoints don't hit.** Confirm you ran `pnpm env:debug` (not `pnpm env:start`). `--xdebug` only takes effect when the container starts, not afterwards.

**File paths look wrong.** If the debugger opens a container-path file instead of your host file, the mappings are incorrect. The container's plugin path is `/var/www/html/wp-content/plugins/mailpoet`, **not** `/wp-content/plugins/mailpoet`.

**Connection refused on Linux.** wp-env uses `host.docker.internal` to reach the IDE. Recent Docker Desktop auto-wires this; older Linux Docker setups may need `--add-host=host.docker.internal:host-gateway` in the Docker run args. Usually this Just Works on current versions.
