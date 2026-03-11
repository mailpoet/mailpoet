#!/bin/bash

# Fetch the WordPress releases RSS feed
RSS_FEED=$(curl -s https://wordpress.org/news/category/releases/feed/)

# Extract the latest version from the feed and convert it to lowercase
LAST_VERSION=$(echo "$RSS_FEED" | grep -o '<title>WordPress [^<]*</title>' | sed -E 's/<\/?title>//g' | head -n 1 | tr [:upper:] [:lower:])

# Check if a beta or RC version is found
if [[ $LAST_VERSION == *'beta'* ]]; then
  # Extract version from the direct download zip link (e.g. wordpress-7.0-beta3.zip)
  LATEST_BETA=$(echo "$RSS_FEED" | grep -o 'wordpress-[0-9\.]*-beta[0-9]*\.zip' | head -n 1 | sed -E 's/wordpress-([0-9\.]+-beta[0-9]*)\.zip/\1/')

  echo "Latest WordPress beta version: $LATEST_BETA"
  echo "LATEST_BETA=$LATEST_BETA"

elif [[ $LAST_VERSION == *'release candidate'* ]]; then
  # Extract version from the direct download zip link (e.g. wordpress-7.0-RC3.zip)
  LATEST_BETA=$(echo "$RSS_FEED" | grep -o 'wordpress-[0-9\.]*-RC[0-9]*\.zip' | head -n 1 | sed -E 's/wordpress-([0-9\.]+-RC[0-9]*)\.zip/\1/')

  echo "Latest WordPress RC version: $LATEST_BETA"
  echo "LATEST_BETA=$LATEST_BETA"
else
  echo "No WordPress beta/RC version found."
  echo "LATEST_BETA="
fi
