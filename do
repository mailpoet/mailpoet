#!/bin/sh

DIR=$(dirname $0)
ROBO_BIN=$DIR/vendor/bin/robo
COMPOSER_BIN=$DIR/tools/vendor/composer.phar

# when some files from 'autoload.files' array in 'composer.json' are missing (i.e. package not installed),
# we need to run 'composer install' before 'RoboFile.php' (it uses 'autoload.php' - chicken & egg problem)
php -r "
  foreach (json_decode(file_get_contents('$DIR' . '/composer.json'), true)['autoload']['files'] as \$file) {
    if (!file_exists(\$file)) exit(1);
  }
"
MISSING_AUTOLOAD_FILES=$?

# executables not found, install dev tools (including Composer) and PHP packages (including Robo)
if [ ! -f $ROBO_BIN ] || [ ! -f $COMPOSER_BIN ] || [ "$MISSING_AUTOLOAD_FILES" -ne "0" ]; then
  COMPOSER_DEV_MODE=1 php tools/install.php
  $COMPOSER_BIN install
fi

# pass command to Robo
$ROBO_BIN "$@"
