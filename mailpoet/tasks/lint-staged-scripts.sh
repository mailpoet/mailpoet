#!/bin/sh

source $PWD/.env

if [ "$MP_GIT_HOOKS_ENABLE" != "true" ]; then
    echo "MP_GIT_HOOKS_ENABLE is not set to 'true'. Skipping lint-staged-scripts."
    exit 0
fi

if [ "$MP_GIT_HOOKS_ESLINT" = "true" ]; then
  eslint --max-warnings 0 $@
else
  echo "MP_GIT_HOOKS_ESLINT not set to 'true', skipping eslint"
fi
