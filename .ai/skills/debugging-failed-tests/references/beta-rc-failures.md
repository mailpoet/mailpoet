# Beta / RC failures (WordPress or WooCommerce)

Nightly CircleCI runs target the latest WordPress and WooCommerce betas / release candidates. A red run on those jobs may be upstream incompatibility, not a regression in our code.

## Spotting a beta/RC run

- The job name often contains `_wordpress_beta` or `_woocommerce_beta`.
- The version printed at the start of the failing step is authoritative — `WORDPRESS VERSION: 6.8-RC1`, `9.5.0-beta.1`, etc. (printed by `tests_env/docker/codeception/docker-entrypoint.sh`).

## Investigating

1. **Confirm the exact beta/RC version** from the entrypoint output.
2. **Reproduce locally against the same version.** See the `running-tests` skill for the WP / WC version flags. If it passes on stable and fails on beta/RC, the version is implicated.
3. **Read the release notes** for breaking changes or deprecations in the area the failing test exercises (hooks, REST routes, function signatures, block API, HPOS, etc.):
   - WordPress: [wordpress.org/news/category/releases](https://wordpress.org/news/category/releases/), [Trac](https://core.trac.wordpress.org/), [WordPress/wordpress-develop](https://github.com/WordPress/wordpress-develop)
   - WooCommerce: [woocommerce/woocommerce releases](https://github.com/woocommerce/woocommerce/releases)
4. **Search for an existing upstream issue** describing the same regression or BC change. **Do not file new upstream issues from this skill** — only reference existing ones in the report. If you find nothing, say so explicitly.

## Fix direction (priority order)

1. **Adapt our code or our test** to the new behaviour if it's a legitimate change (deprecation, new signature, intentional behaviour change). Default and preferred.
2. **Plugin-side workaround** if the change looks like an unintentional upstream regression but adapting cleanly isn't feasible. Guard the workaround with a version check (`version_compare()`, or feature detection) so it activates only on affected versions — **never** branch on "is this a beta."

The default fix target is still our plugin or our test, not the upstream beta.

## Related

- `mailpoet-beta-compat-test` — broader compatibility-testing flow when a new beta/RC ships.
