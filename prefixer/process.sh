#!/usr/bin/env bash

BASEDIR=$(dirname "$0")
DRY_RUN_CHECK=$($BASEDIR/../composer.phar install --working-dir=$BASEDIR --dry-run 2>&1 | grep 'Nothing to install or update')

if [[ $DRY_RUN_CHECK != "Nothing to install or update" ]]; then
    rm -rf $BASEDIR/build
fi
if [[ ! -e $BASEDIR/build || -z $(ls -A $BASEDIR/../vendor-prefixed) ]]; then
    $BASEDIR/../composer.phar install --working-dir=$BASEDIR
fi
