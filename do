#!/bin/sh

DIR=$(dirname $0)
ROBO_BIN=$DIR/vendor/bin/robo
COMPOSER_BIN=$DIR/tools/vendor/composer.phar

# executables not found, install dev tools (including Composer) and PHP packages (including Robo)
if [ ! -f $ROBO_BIN ] || [ ! -f $COMPOSER_BIN ]; then
  COMPOSER_DEV_MODE=1 php tools/install.php
  $COMPOSER_BIN install
fi

# pass command to Robo
$ROBO_BIN "$@"
