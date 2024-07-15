#!/bin/bash

# Fetch the WordPress releases RSS feed
RSS_FEED=$(curl -s https://wordpress.org/news/category/releases/feed/)

# Extract the latest version from the feed and convert it to lowercase
LAST_VERSION=$(echo "$RSS_FEED" | grep -o '<title>WordPress [^<]*</title>' | sed -E 's/<\/?title>//g' | head -n 1 | tr [:upper:] [:lower:])

# Check if a beta or RC version is found
if [[ $LAST_VERSION == *'beta'* ]]; then
  # Extract titles containing beta versions from the feed
  VERSION_LINE=$(echo "$RSS_FEED" | grep -o '<code>wp core update [^<]*</code>' | sed -E 's/<\/?code>//g' | head -n 1 | grep 'beta')
  LATEST_BETA=$(echo "$VERSION_LINE" | sed -E 's/.*--version=([0-9\.]+-beta[0-9]+).*/\1/')

  echo "Latest WordPress beta version: $LATEST_BETA"
  echo "LATEST_BETA=$LATEST_BETA"

elif [[ $LAST_VERSION == *'release candidate'* ]]; then
  # Extract titles containing RC versions from the feed
  VERSION_LINE=$(echo "$RSS_FEED" | grep -o '<code>wp core update [^<]*</code>' | sed -E 's/<\/?code>//g' | head -n 1 | grep 'RC')
  LATEST_BETA=$(echo "$VERSION_LINE" | sed -E 's/.*--version=([0-9\.]+-RC[0-9]+).*/\1/')

  echo "Latest WordPress RC version: $LATEST_BETA"
  echo "LATEST_BETA=$LATEST_BETA"
else
  echo "No WordPress beta/RC version found."
  echo "LATEST_BETA="
fi
