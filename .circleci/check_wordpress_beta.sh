#!/bin/bash

# Fetch the WordPress releases RSS feed
RSS_FEED=$(curl -s https://wordpress.org/news/category/releases/feed/)

# Release titles, newest first, in lowercase
TITLES=$(echo "$RSS_FEED" | grep -o '<title>WordPress [^<]*</title>' | sed -E 's/<\/?title>//g' | tr [:upper:] [:lower:])

# A minor release (e.g. 7.1.4) can be published after the current beta, so the newest post isn't always the beta
LAST_VERSION=$(echo "$TITLES" | grep -E 'beta|release candidate' | head -n 1)
LAST_STABLE_VERSION=$(echo "$TITLES" | grep -vE 'beta|release candidate' | head -n 1 | grep -oE '[0-9]+\.[0-9]+' | head -n 1)

# Skip a beta or RC whose version is already released (e.g. 7.2-RC3 after 7.2)
LAST_PRERELEASE_VERSION=$(echo "$LAST_VERSION" | grep -oE '[0-9]+\.[0-9]+' | head -n 1)
if [[ -n $LAST_STABLE_VERSION && -n $LAST_PRERELEASE_VERSION ]] && [[ $(printf '%s\n%s\n' "$LAST_STABLE_VERSION" "$LAST_PRERELEASE_VERSION" | sort -V | tail -n 1) == "$LAST_STABLE_VERSION" ]]; then
  LAST_VERSION=''
fi

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
