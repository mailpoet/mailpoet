#!/bin/sh

# Translations (npm install & composer install need to be run before)
./do makepot

plugin_name='mailpoet'

# Remove previous build.
rm $plugin_name.zip

# Create temp dir.
mkdir $plugin_name

# Production assets.
rm -rf node_modules
npm install
./do compile:all

# Production libraries.
./composer.phar install --no-dev --prefer-dist --optimize-autoloader

# Copy release folders.
cp -Rf lang $plugin_name
cp -RfL assets $plugin_name
cp -Rf lib $plugin_name
cp -Rf vendor $plugin_name
cp -Rf views $plugin_name
rm -Rf $plugin_name/assets/css/src
rm -Rf $plugin_name/assets/js/src

# Remove extra files from 3rd party extensions
find $plugin_name/vendor/ -type f -regextype posix-egrep -iregex ".*\/*\.(markdown|md|txt)" -print0 | xargs -0 rm -f
find $plugin_name/vendor/ -type f -regextype posix-egrep -iregex ".*\/(readme|license|version|changes)" -print0 | xargs -0 rm -f
find $plugin_name/vendor -type d -regextype posix-egrep -iregex ".*\/(testing|docs?|examples?|\.git)" -print0 | xargs -0 rm -rf

# Specific files to remove
rm $plugin_name/vendor/j4mie/idiorm/demo.php

# Copy release files.
cp LICENSE $plugin_name
cp index.php $plugin_name
cp $plugin_name.php $plugin_name
cp readme.txt $plugin_name
cp uninstall.php $plugin_name

# Zip final release.
zip -r $plugin_name.zip $plugin_name

# Remove temp dir.
rm -rf $plugin_name

# Reinstall dev dependencies.
./composer.phar install
