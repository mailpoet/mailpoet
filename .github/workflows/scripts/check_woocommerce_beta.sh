#!/bin/bash

# Fetch the WooCommerce versions from the WordPress API. Fail when they can’t be
# fetched or read, so an error isn’t taken for “no beta is out”.
VERSIONS_JSON=$(curl -sSf --retry 3 https://api.wordpress.org/plugins/info/1.0/woocommerce.json) || {
  echo "Could not fetch the WooCommerce versions." >&2
  exit 1
}

# Find the latest version
# Sorting: -dev < -alpha < -beta < -rc < final (no suffix)
LATEST_VERSION=$(
  echo "$VERSIONS_JSON" | \
  jq -r '.versions | keys_unsorted | .[]' | \
  grep -v 'trunk' | \
  awk '{
    orig = $0
    v = $0
    if (v ~ /-dev/) { gsub(/-dev/, "~0dev", v) }
    else if (v ~ /-alpha/) { gsub(/-alpha/, "~1alpha", v) }
    else if (v ~ /-beta/) { gsub(/-beta/, "~2beta", v) }
    else if (v ~ /-rc/) { gsub(/-rc/, "~3rc", v) }
    else { v = v "~4" }
    print v "\t" orig
  }' | \
  sort -V -k1,1 | \
  tail -n 1 | \
  cut -f2
)

if [[ -z $LATEST_VERSION ]]; then
  echo "The WordPress API returned no WooCommerce versions." >&2
  exit 1
fi

# Check if the latest version is a beta/RC version
if [[ $LATEST_VERSION != *'beta'* && $LATEST_VERSION != *'rc'* ]]; then
  echo "No WooCommerce beta/RC version found."
  echo "LATEST_BETA="
else
  echo "Latest WooCommerce beta/RC version: $LATEST_VERSION"
  echo "LATEST_BETA=$LATEST_VERSION"
fi
