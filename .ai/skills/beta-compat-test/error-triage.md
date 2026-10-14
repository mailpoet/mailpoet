# Error triage

Used by the `beta-compat-test` skill when any test fails.

When any test fails, classify each error into one of three categories and take the corresponding action:

## 1. MailPoet error — fix it

The error is in code from `mailpoet/` or `mailpoet-premium/` (e.g., a PHP fatal in `lib/`, a JS error from `assets/js/src/`, a failing test in `tests/`).

**Action:** Open a feature branch (use the `starting-branch` skill), fix the issue, add or update a test, run `pnpm qa` and the relevant `pnpm test:*`, and open a draft PR via the `creating-pull-requests` skill. Do not commit straight to `trunk`.

## 2. QIT / test environment error — report to #qit

The error is in the QIT tool itself or the testing environment (QIT command crashes, Docker setup failures, infrastructure timeouts, flaky runner behavior unrelated to plugin code).

**Action:** Draft a Slack message for the `#qit` channel including the QIT command, the version flags used, and the full error output. Show the draft to the requester before sending — never post without confirmation.

## 3. WooCommerce / WordPress / other plugin error — report upstream

The error is caused by WooCommerce core, WordPress core, or another bundled plugin (e.g., a WC REST API regression, a deprecation in a WP beta, a breaking change in the block editor).

**Action:**

1. Use the `context-a8c` MCP to identify which team owns the affected area (search Linear, Slack, GitHub for the relevant component).
2. Draft a Slack message for `#woo-core-releases` (for WC) or the WordPress core release channel (for WP), describing:
   - The beta version under test
   - The specific error and which test surfaced it
   - A ping to the responsible team identified above
3. Show the draft to the requester before sending.
