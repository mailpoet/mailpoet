#!/bin/bash

TICKET=""
LATEST=false

# Process --ticket command-line argument
for arg in "$@"
do
  if [[ $arg == --ticket=* ]]; then
    TICKET="${arg#*=}"

    # Remove the --ticket argument from the arguments array
    set -- "${@/$arg}"
  fi
  if [[ "$argument" == -L ]]; then
      LATEST=true
      set -- "${@/$arg}"
  fi
done

if [ -z "$TICKET" ]; then
  echo "Please specify a ticket with --ticket=<ticket>"
  exit 1
fi

pnpm outdated "$@"
OUTDATED_PACKAGES=$(pnpm outdated --no-table "$@")

exclude_packages=(
  "tinymce"
  "react-router-dom"
  "react-tooltip"
  "codemirror"
  "@babel/preset-env"
  "react-string-replace"
  "babel-loader"
  "stylelint"
  "backbone"
  "backbone.marionette"
  "history"
  "fork-ts-checker-webpack-plugin"
)

# Loop over each line in $OUTDATED_PACKAGES
COUNTER=0
while IFS= read -r line; do
  let COUNTER=COUNTER+1

  # If the line number modulo 3 equals 1 (so it's the first, fourth, seventh, etc. line),
  # the line contains a package name
  if [ $((COUNTER%3)) -eq 1 ]; then
    # Remove (dev) if present
    PACKAGE_NAME=$(echo "$line" | sed 's/ (dev)//g')

    if [[ $PACKAGE_NAME == @wordpress* ]]; then
      continue
    fi

    # Check if the dependency is in the list of packages to exclude
    if [[ " ${exclude_packages[@]} " =~ " ${PACKAGE_NAME} " ]]; then
      echo "Skipping $PACKAGE_NAME..."
      continue
    fi

    echo "Updating $PACKAGE_NAME..."

    old_version=$(pnpm list "$PACKAGE_NAME" | awk '{print $2}' | tail -n 1)

    if $LATEST; then
      pnpm up -L "$PACKAGE_NAME"
    else
      pnpm up "$PACKAGE_NAME"
    fi

    if [ $? -eq 0 ]; then
      echo "$PACKAGE_NAME updated successfully."

      set -e
      ./do install:js
      ./do compile:js
      ./do compile:css
      ./do qa:frontend-assets
      set +e

      new_version=$(pnpm list "$PACKAGE_NAME" | awk '{print $2}' | tail -n 1)

      git add ./package.json
      git commit --no-verify -F- <<EOF
Update $PACKAGE_NAME from $old_version to $new_version

$TICKET
EOF
    else
      echo "Update of $PACKAGE_NAME failed. Exiting."
      exit 1
    fi
  fi
done <<< "$OUTDATED_PACKAGES"
