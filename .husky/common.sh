#!/usr/bin/env bash

fileChanged() {
  local filePattern="$1"
  local changedFiles="$2"
  if echo "$changedFiles" | grep -qE "$filePattern"; then
      return 0
  else
      return 1
  fi
}

installIfUpdates() {
  local changedFiles="$(git diff-tree -r --name-only --no-commit-id HEAD@{1} HEAD)"

  if [ "$MP_GIT_HOOKS_INSTALL_JS" = "true" ] && fileChanged "pnpm-lock.yaml" "$changedFiles"; then
    echo "Change detected in pnpm-lock.yaml, running do install:js"
    pushd mailpoet
    ./do install:js
    popd
  fi

  if [ "$MP_GIT_HOOKS_INSTALL_PHP" = "true" ] && fileChanged "mailpoet/composer.lock" "$changedFiles"; then
    echo "Change detected in mailpoet/composer.lock, running do install:php"
    pushd mailpoet
    ./do install:php
    popd
  fi
}
