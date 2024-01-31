#!/bin/sh

set -e

source $PWD/.env

if [ "$MP_GIT_HOOKS_ENABLE" != "true" ]; then
  echo "MP_GIT_HOOKS_ENABLE is not set to 'true'. Skipping lint-staged-php."
  exit 0
fi

if [ "$MP_GIT_HOOKS_PHPLINT" = "true" ]; then
  phplint $@
else
  echo "MP_GIT_HOOKS_PHPLINT not set to 'true', skipping phplint"
fi

if [ "$MP_GIT_HOOKS_CODE_SNIFFER" = "true" ]; then
  ./do qa:code-sniffer $@
else
  echo "MP_GIT_HOOKS_CODE_SNIFFER not set to 'true', skipping code sniffer"
fi

if [ "$MP_GIT_HOOKS_MINIMAL_PLUGIN_STANDARDS" = "true" ]; then
  ./do qa:minimal-plugin-standard $@
else
  echo "MP_GIT_HOOKS_MINIMAL_PLUGIN_STANDARDS not set to 'true', skipping minimal plugin standards"
fi

if [ "$MP_GIT_HOOKS_PHPSTAN" = "true" ]; then
  bash -c './do qa:phpstan' $@
else
  echo "MP_GIT_HOOKS_PHPSTAN not set to 'true', skipping PHPStan"
fi

