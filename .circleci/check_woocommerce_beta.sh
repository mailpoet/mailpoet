#!/bin/bash

# Fetch the versions of WooCommerce from the WordPress API
VERSIONS=$(curl -s https://api.wordpress.org/plugins/info/1.0/woocommerce.json | jq -r '.versions | keys_unsorted | .[]' | grep -v 'trunk')
LATEST_VERSION=""

# Find the latest version
for version in $VERSIONS; do
  LATEST_VERSION=$version
done

# Check if the latest version is a beta version
if [[ $LATEST_VERSION != *'beta'* ]]; then
  echo "No WooCommerce beta version found."
  echo "LATEST_BETA="
else
  echo "Latest WooCommerce beta version: $LATEST_VERSION"
  echo "LATEST_BETA=$LATEST_VERSION"
fi
