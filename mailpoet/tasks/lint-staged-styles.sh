#!/bin/sh

source $PWD/.env

if [ "$MP_GIT_HOOKS_ENABLE" != "true" ]; then
    echo "MP_GIT_HOOKS_ENABLE is not set to 'true'. Skipping lint-staged-scripts."
    exit 0
fi

if [ "$MP_GIT_HOOKS_STYLELINT" = "true" ]; then
  pnpm run stylelint $@
else
  echo "MP_GIT_HOOKS_STYLELINT not set to 'true', skipping stylelint"
fi
