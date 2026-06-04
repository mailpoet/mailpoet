#!/usr/bin/env bash

# Git runs hooks with the working directory at the worktree root, so $PWD/mailpoet
# points at the worktree that triggered the hook — the same dir ./do uses.
ENV_DIR="$PWD/mailpoet"
# .env is gitignored, so fresh clones and new worktrees don't have one yet. Seed
# it from the sample so sourcing it below (and ./do, which loads .env on every
# run) doesn't fail.
if [ ! -f "$ENV_DIR/.env" ] && [ -f "$ENV_DIR/.env.sample" ]; then
  cp "$ENV_DIR/.env.sample" "$ENV_DIR/.env"
fi

. "$ENV_DIR/.env"

export MP_GIT_HOOKS_ENABLE="${MP_GIT_HOOKS_ENABLE:-true}"
export MP_GIT_HOOKS_ESLINT="${MP_GIT_HOOKS_ESLINT:-true}"
export MP_GIT_HOOKS_STYLELINT="${MP_GIT_HOOKS_STYLELINT:-true}"
export MP_GIT_HOOKS_PHPLINT="${MP_GIT_HOOKS_PHPLINT:-true}"
export MP_GIT_HOOKS_CODE_SNIFFER="${MP_GIT_HOOKS_CODE_SNIFFER:-true}"
export MP_GIT_HOOKS_PHPSTAN="${MP_GIT_HOOKS_PHPSTAN:-true}"
export MP_GIT_HOOKS_INSTALL_JS="${MP_GIT_HOOKS_INSTALL_JS:-false}"
export MP_GIT_HOOKS_INSTALL_PHP="${MP_GIT_HOOKS_INSTALL_PHP:-false}"

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

