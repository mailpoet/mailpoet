# Mailpit Setup

The dev environment routes all outgoing mail to [Mailpit](https://mailpit.axllent.org/), a local SMTP catcher running as a Docker container. Mailpit replaces the old MailHog setup and is started/stopped automatically by `pnpm env:start` and `pnpm env:stop`.

---

## How it works

- `pnpm env:start` runs `scripts/launch-smtp-catcher.sh`, which starts an `axllent/mailpit` container named `mailpoet-smtp`. Web UI on `8082`, SMTP on `1026`.
- `.wp-env/mu-plugins/mailpoet-dev-smtp.php` is mounted into the wp-env container and hooks `phpmailer_init` to route `wp_mail()` through SMTP at `host.docker.internal:1026`.
- `.wp-env/scripts/configure-mailpoet-dev-smtp.php` runs via `lifecycleScripts.afterStart`. It configures MailPoet's own mailer (`mta_group=smtp`, `smtp_provider=manual`, `mta.method=SMTP`, host/port aligned with Mailpit, `mta.authentication=-1` for the No radio), seeds a default sender, and marks the MailPoet welcome wizard as completed so it doesn't overwrite the config on first admin visit. Idempotent — no-op once everything is in place.
- `host.docker.internal` resolves from inside the container to the host machine where Mailpit is listening.
- `pnpm env:stop` and `pnpm env:destroy` run `scripts/stop-smtp-catcher.sh` to stop the container.
- Captured emails persist in a named docker volume (`mailpoet-smtp-data`) so they survive container restarts.

**Web UI:** http://localhost:8082

Both `wp_mail()` (WordPress notifications, plugin emails, etc.) **and** MailPoet's own newsletter mailer are routed through Mailpit with zero manual setup after `pnpm bootstrap && pnpm env:start`.

---

## Why port 1026?

`tests_env/` ships its own MailHog container that binds host port **1025** (the standard SMTP port for local catchers). Putting Mailpit on **1026** lets both stacks run concurrently without colliding — e.g. you can have `pnpm env:start` up while running `pnpm test:integration`.

The port constant is set in `.wp-env.json`:

```json
"MAILPOET_DEV_SMTP_PORT": 1026
```

and read by the mu-plugin. If you need a different port, change both the `.wp-env.json` constant and the `-p 1026:1025` flag in `scripts/launch-smtp-catcher.sh`.

---

## MailPoet newsletter mailer

MailPoet uses its **own** mailer for newsletters by default (separate from WordPress's `wp_mail()`). The `lifecycleScripts.afterStart` hook auto-configures it on every `pnpm env:start`:

- `mta_group` = `smtp`
- `smtp_provider` = `manual` (drives the UI dropdown to "Your own SMTP server")
- `mta.method` = `SMTP`
- `mta.host` / `mta.port` = `host.docker.internal` / `1026`
- `mta.authentication` = `-1` (the UI expects `'1'` for Yes, `'-1'` for No; `'0'` leaves both radios unchecked)
- `sender` = `MailPoet Dev <dev@mailpoet.local>` (only when unset)
- `version` = MailPoet's current version, which marks the welcome wizard as completed (otherwise the wizard would overwrite `mta` back to `PHPMail` on the first admin visit)

**Want to test a different send method?** Change it in **wp-admin → MailPoet → Settings → Advanced**. Note that the afterStart hook re-applies the SMTP config on every `pnpm env:start`, so any manual change will be reverted next time the env boots. For full manual control, override the hook from your `.wp-env.override.json` so `.wp-env.json` stays untouched:

```json
{
  "$schema": "https://schemas.wp.org/trunk/wp-env.json",
  "lifecycleScripts": {
    "afterStart": ""
  }
}
```

This is the pattern to use when testing MailPoet Sending Service (MSS) on a custom domain — configure the MSS key in the UI once and the override keeps `afterStart` from stomping it on the next `env:start`.

> **Heads-up:** `pnpm bootstrap` regenerates the `plugins` array in `.wp-env.override.json` from the filesystem (WooCommerce plugin zips, `mailpoet-premium/`, etc.). Non-plugin keys like `lifecycleScripts` or `config` are preserved, but any plugin path you added manually will be dropped. Put personal plugin tweaks under a non-plugin key, or skip `pnpm bootstrap` after customizing the file.

---

## Verify routing works

Send a quick test from wp-cli:

```bash
pnpm wp eval 'wp_mail("test@example.com", "Hello from wp-env", "This is a test.");'
```

Then open http://localhost:8082 — the test email should appear in the Mailpit inbox.

---

## Managing captured mail

Mailpit stores emails in a SQLite database inside the `mailpoet-smtp-data` docker volume. The UI supports search, HTML/text view, raw source, and API access.

```bash
# List docker volumes
docker volume ls | grep mailpoet-smtp-data

# Wipe all captured emails (container must be stopped first)
pnpm env:stop
docker volume rm mailpoet-smtp-data
pnpm env:start    # recreates the volume empty
```

---

## Troubleshooting

**Nothing appears in Mailpit after sending.**

1. Check the container is running: `docker ps | grep mailpoet-smtp`
2. Confirm it's listening on 1026 from the host: `nc -zv localhost 1026` (or `telnet localhost 1026`)
3. Check `.wp-env.json` has `MAILPOET_DEV_SMTP_HOST: "host.docker.internal"` and `MAILPOET_DEV_SMTP_PORT: 1026`
4. Restart wp-env to pick up config changes: `pnpm env:restart`

**`host.docker.internal` doesn't resolve (Linux).**

Recent Docker Desktop provides this hostname automatically. Some older Linux Docker installs don't — you can work around by replacing `host.docker.internal` with the host's Docker bridge IP (often `172.17.0.1`) in `.wp-env.json`, then `pnpm env:restart`.

**MailPoet's "Send test email" button hangs / fails.**

The MailPoet UI uses MailPoet's own mailer, not `wp_mail`. Configure MailPoet's SMTP settings in wp-admin (see above) or change **Send with…** to a method that uses `wp_mail`.
