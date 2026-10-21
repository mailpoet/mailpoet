---
name: beta-compat-test
description: Use when a new WooCommerce or WordPress beta or release candidate is out and MailPoet's compatibility with it needs testing. Triggers on phrases like "test against WC beta", "test MailPoet on WP beta", "compatibility test", "new WooCommerce version", "new WordPress version".
---

# MailPoet Beta Compatibility Test

## Overview

Run a full compatibility test of the released MailPoet plugin against a new WooCommerce or WordPress beta / release candidate. Covers QIT automated tests, CircleCI scheduled beta runs, and manual smoke tests in the local wp-env.

## Usage

Invoke with the target beta version(s) as argument:

```
/beta-compat-test wc=10.6.0-beta.1
/beta-compat-test wp=6.9-RC1
/beta-compat-test wc=10.6.0-beta.1 wp=6.9-RC1
```

If neither is provided, detect the latest beta automatically:

```bash
.circleci/check_woocommerce_beta.sh
.circleci/check_wordpress_beta.sh
```

Both scripts print a `LATEST_BETA=…` line (empty if none).

## Process

Follow all four steps sequentially. Report results after each step before moving to the next.

### Step 1 — Download the latest stable MailPoet zip

Compatibility testing must run against the _released_ plugin, not local trunk. Download from wordpress.org:

```bash
curl -L -o mailpoet/mailpoet.zip \
  "https://downloads.wordpress.org/plugin/mailpoet.latest-stable.zip"
```

The QIT Robo tasks expect the file at `mailpoet/mailpoet.zip` (see `mailpoet/RoboFile.php:9` — `ZIP_BUILD_PATH`). Verify the download succeeded and the file is a valid zip.

### Step 2 — Run QIT commands

MailPoet wraps QIT in Robo tasks that take `--wp` and `--wc` version flags. Run from `mailpoet/`:

```bash
cd mailpoet

# Activation test — boots WP, activates MailPoet, fails on PHP errors / hook misuse
./do qa:qit-activation --wp=<WP_VERSION> --wc=<WC_VERSION>

# WooCommerce REST API regression suite
./do qa:qit-woo-api --wp=<WP_VERSION> --wc=<WC_VERSION>

# WooCommerce E2E suite
./do qa:qit-woo-e2e --wp=<WP_VERSION> --wc=<WC_VERSION>
```

Pass only the versions under test — omitted flags default to `stable`. For `--wc`, use the exact beta version from the request first. Only if QIT rejects it, fall back to `X.Y.Z-dev` (e.g., `10.6.0-beta.1` → `10.6.0-dev`). Use `./vendor/bin/qit run:woo-api --help` from `mailpoet/` to discover the enumerated values.

You must authenticate QIT once per machine:

```bash
./vendor/bin/qit partner:add --user="$QIT_PARTNER_USER" --application_password="$QIT_PARTNER_SECRET"
```

After each command completes, fetch the HTML report from the `Result Url` printed in the output:

```bash
grep "Result Url" <captured-output> | awk '{ print $3 }' | xargs curl -o /tmp/qit-report.html
```

Summarize pass/fail status and highlight any failures with details.

### Step 3 — Check the CircleCI beta workflows

The scheduled `nightly` workflow on `trunk` picks up the latest WooCommerce and WordPress beta automatically. Confirm its beta jobs (`integration_tests_woocommerce_beta`, `acceptance_tests_woocommerce_beta`, the WordPress beta jobs, and the `*_with_premium_*` jobs when premium features are in scope) passed and installed the expected version. Do not trigger a pipeline with beta parameters unless `.circleci/config.yml` declares them at the top level. API calls and steps: [circleci-beta-jobs.md](circleci-beta-jobs.md).

### Step 4 — Smoke tests in local wp-env

Start the local environment with the target beta installed:

```bash
pnpm env:start
pnpm compile
# Pin WordPress version (if testing WP beta)
pnpm exec wp-env run cli -- wp core update --version=<WP_VERSION> --force
```

If `pnpm env:start` fails because port 80 is already occupied, inspect the running containers or process that owns the port and ask the requester how to proceed before stopping it or changing the wp-env port.

Do not use `pnpm wp ... --version=<VERSION>` for commands that pass flags to WP-CLI. The `pnpm wp` script expands to `wp-env run cli wp`, and `wp-env run` can consume inner flags unless the command includes the `--` separator. Prefer `pnpm exec wp-env run cli -- wp ...` when passing flags.

For WooCommerce beta versions, do not rely on `wp plugin install woocommerce --version=<WC_VERSION>`. It may resolve to the latest stable wordpress.org package instead of the requested beta. Use the same WooCommerce zip download task that CI uses, then install that zip into wp-env:

```bash
cd mailpoet
./do download:woo-commerce-zip <WC_VERSION>
cd ..
pnpm exec wp-env run cli -- wp plugin install /var/www/html/wp-content/plugins/mailpoet/tests/plugins/woocommerce.zip --force --activate
pnpm exec wp-env run cli -- wp wc update
pnpm exec wp-env run cli -- wp plugin get woocommerce --field=version
```

Do not run `pnpm wp plugin install mailpoet/mailpoet.zip --force` in the normal local wp-env when `./mailpoet` is configured as a bind-mounted plugin. That can overwrite the local checkout because WordPress writes into the mounted plugin directory.

Use the local checkout for smoke tests after the released zip has already passed QIT, or create a separate temporary wp-env config whose `plugins` entry points to an extracted copy of the released zip outside the repository. If using a temporary release copy, activate that copy in the temporary environment instead of installing the zip over the bind mount.

Then use an available browser automation tool, such as Playwright MCP or Chrome MCP, at the local wp-env URL (admin / password) and run these smoke tests. Use `pnpm exec wp-env status` to confirm the URL because local overrides may change the default `http://localhost:8888/` address. Watch the browser console and `pnpm env:logs` for PHP/JS errors throughout.

1. **MailPoet menu loads** — Navigate to `MailPoet → Homepage`. Verify it loads without PHP/JS errors.
2. **Subscribers list** — Open `MailPoet → Subscribers`. Verify the DataViews-based list renders, paginates, and a known subscriber is visible.
3. **Send a standard newsletter** — `MailPoet → Emails → New → Newsletter`. Pick the block-based editor, save, send to a test segment, and confirm Mailpit (`http://localhost:8082`) captures the email.
4. **Subscription form** — `MailPoet → Forms`. Edit a form, save. On the frontend, submit the form and confirm the new subscriber appears in the list.
5. **Automation** — `MailPoet → Automations`. Activate a “Welcome email” automation, subscribe a new user, confirm the welcome email is delivered to Mailpit.
6. **WooCommerce integration** (if `wc=` was set) — Place a guest order in the shop. Confirm the WC abandoned-cart / first-purchase automation fires and the customer appears in the synced WooCommerce segments.

Report the result of each check. Provide a screenshot of any failure and capture the relevant entry from `pnpm env:logs`.

## After testing

Summarize all results in a table:

| Test                                   | Status | Notes |
| -------------------------------------- | ------ | ----- |
| QIT Activation                         | ✅/❌  |       |
| QIT Woo API                            | ✅/❌  |       |
| QIT Woo E2E                            | ✅/❌  |       |
| CircleCI: free plugin beta pipeline    | ✅/❌  |       |
| CircleCI: premium plugin beta pipeline | ✅/❌  |       |
| Smoke: MailPoet menu loads             | ✅/❌  |       |
| Smoke: Subscribers list                | ✅/❌  |       |
| Smoke: Send a standard newsletter      | ✅/❌  |       |
| Smoke: Subscription form               | ✅/❌  |       |
| Smoke: Automation                      | ✅/❌  |       |
| Smoke: WooCommerce integration         | ✅/❌  |       |

## Error triage

Classify each failure as a MailPoet error (fix it on a branch), a QIT or environment error (draft a `#qit` message), or an upstream WooCommerce/WordPress error (draft a message to the owning team). Actions for each: [error-triage.md](error-triage.md).
