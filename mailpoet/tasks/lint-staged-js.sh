#!/bin/sh

set -e

source $PWD/.env

if [ "$MP_GIT_HOOKS_ENABLE" != "true" ]; then
    echo "MP_GIT_HOOKS_ENABLE is not set to 'true', skipping lint-staged-js"
    exit 0
fi

if [ "$MP_GIT_HOOKS_ESLINT" = "true" ]; then
  eslint --max-warnings 0 $@
else
  echo "MP_GIT_HOOKS_ESLINT is not set to 'true', skipping eslint"
fi
