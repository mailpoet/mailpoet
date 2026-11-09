#!/usr/bin/env bash
# Downloads the paid plugins the WooCommerce tests need into mailpoet/tests/plugins.
# CircleCI and GitHub Actions both call it, so they test the same versions.
# .github/workflows/scripts/helpers.php updates the versions below.
# Needs GH_TOKEN with access to the private plugin repositories.

set -euo pipefail

cd "$(dirname "$0")/../mailpoet"

# GitHub sometimes fails a download for a few minutes (HTTP 500, or a release
# it briefly can’t find), so try each one a few times.
download() {
  for attempt in 1 2 3; do
    "$@" && return
    if [[ $attempt -lt 3 ]]; then
      echo "Download failed, trying again in 30 seconds."
      sleep 30
    fi
  done
  return 1
}

# A run that tests other versions sets these, and gets those instead.
download ./do download:woo-commerce-zip "${WOO_CORE_VERSION:-11.2.2}"
download ./do download:woo-commerce-subscriptions-zip "${WOO_SUBSCRIPTIONS_VERSION:-9.3.0}"
download ./do download:woo-commerce-memberships-zip "${WOO_MEMBERSHIPS_VERSION:-1.30.2}"
download ./do download:automate-woo-zip "${AUTOMATE_WOO_VERSION:-6.9.5}"
