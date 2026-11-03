#!/usr/bin/env bash
# Downloads the paid plugins the WooCommerce tests need into mailpoet/tests/plugins.
# CircleCI and GitHub Actions both call it, so they test the same versions.
# .github/workflows/scripts/helpers.php updates the versions below.
# Needs GH_TOKEN with access to the private plugin repositories.

set -euo pipefail

cd "$(dirname "$0")/../mailpoet"

# A run that tests other versions sets these, and gets those instead.
./do download:woo-commerce-zip "${WOO_CORE_VERSION:-11.2.1}"
./do download:woo-commerce-subscriptions-zip "${WOO_SUBSCRIPTIONS_VERSION:-9.3.0}"
./do download:woo-commerce-memberships-zip "${WOO_MEMBERSHIPS_VERSION:-1.30.2}"
./do download:automate-woo-zip "${AUTOMATE_WOO_VERSION:-6.9.4}"
