#!/bin/sh

DIR=$(dirname $0)
ROBO=$DIR/vendor/bin/robo

# Robo executable not found, install dev tools (including Composer) and PHP packages (including Robo)
if [ ! -f $ROBO ]; then
  COMPOSER_DEV_MODE=1 php tools/install.php
  ./tools/vendor/composer.phar install
fi

# pass command to Robo
$ROBO "$@"
