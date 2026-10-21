# Test runner flags

Part of the `running-tests` skill.

`pnpm` forwards extra args transparently — `pnpm test:integration --file=X --debug` ends up as `cd mailpoet && ./do test:integration --skip-deps --file=X --debug`. Every flag below works through the `pnpm` wrapper.

Source of truth: `mailpoet/RoboFile.php` (search for `public function test*`).

- `--file=<path>[:method]` — all suites. Run a single file, or one method within it.
- `--debug` — unit, integration (incl. woo/base variants). Codeception verbose mode: full stack traces, per-step output. Not on acceptance.
- `--group=<name>` — integration, acceptance (incl. multisite). Filter to tests tagged `@group <name>` (e.g. `woo`, `failed`).
- `--skip-group=<name>` — integration only. Exclude a group.
- `--multisite` — integration (incl. woo/base). Acceptance has its own task (`./do test:acceptance-multisite`). Vestigial on unit — accepted by the signature but the suite never boots WP, so it has no effect.
- `--stop-on-fail` — integration only. Halt on the first failure.
- `--skip-plugins` — integration, acceptance. Don’t load extra plugins (WC, etc.).
- `--enable-hpos` / `--disable-hpos` / `--enable-hpos-sync` — integration (incl. variants), acceptance (incl. multisite). Toggle WooCommerce High-Performance Order Storage for the run. Use when your change touches WC order data paths.
- `--blockbased-theme` — acceptance only, free plugin only. Activates Twenty Twenty-Four (a block theme) instead of Twenty Twenty-One, as CI’s `acceptance_tests_blockbased_theme` job does.
- `--wordpress-version=<ver>` — integration, acceptance. Run against a specific WP version, including beta/RC — see [[beta-compat-test]].
- `--timeout=<seconds>` — acceptance, acceptance-multisite. Per-test timeout override.
- `--skip-deps` — integration, acceptance (incl. multisite). Skip in-container `composer install`. The `pnpm test:integration` / `:acceptance` wrappers (and their premium variants) pass this by default.
- `--xml[=path]` — all suites. Write JUnit XML output. Used by CI; rarely needed locally.

When the wrapper isn’t enough — direct Codeception access, artifact inspection, or DB poking — shell in (see “Shelling in” below).
