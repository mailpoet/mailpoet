#!/usr/bin/env bash

BASEDIR=$(dirname "$0")
COMPOSER_BIN=$BASEDIR/../tools/vendor/composer.phar
VENDOR_PREFIXED_DIR=$BASEDIR/../vendor-prefixed
DRY_RUN_CHECK=$($COMPOSER_BIN install --working-dir=$BASEDIR --dry-run 2>&1 | grep 'Nothing to install or update')

if [[ $DRY_RUN_CHECK != "Nothing to install or update" ]]; then
    rm -rf $BASEDIR/build
fi

mkdir -p $VENDOR_PREFIXED_DIR
if [[ ! -e $BASEDIR/build || -z $(ls -A $VENDOR_PREFIXED_DIR) ]]; then
    $COMPOSER_BIN install --working-dir=$BASEDIR
fi
