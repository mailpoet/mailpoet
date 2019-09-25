#!/usr/bin/env bash

BASEDIR=$(dirname "$0")
COMPOSER_BIN=$BASEDIR/../tools/vendor/composer.phar
DRY_RUN_CHECK=$($COMPOSER_BIN install --working-dir=$BASEDIR --dry-run 2>&1 | grep 'Nothing to install or update')

if [[ $DRY_RUN_CHECK != "Nothing to install or update" ]]; then
    rm -rf $BASEDIR/build
fi
if [[ ! -e $BASEDIR/build || -z $(ls -A $BASEDIR/../vendor-prefixed) ]]; then
    $COMPOSER_BIN install --working-dir=$BASEDIR
fi
