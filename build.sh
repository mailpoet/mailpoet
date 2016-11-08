#!/bin/sh

# Translations (npm install & composer install need to be run before)
echo '[BUILD] Generating translations'
./do makepot

plugin_name='mailpoet'

# Remove previous build.
echo '[BUILD] Removing previous build'
rm $plugin_name.zip

# Create temp dir.
echo '[BUILD] Creating temporary directory'
mkdir $plugin_name

# Production assets.
echo '[BUILD] Generating production CSS and JS assets'
rm -rf node_modules
npm install
./do compile:all

# Production libraries.
echo '[BUILD] Fetching production libraries'
rm -rf vendor
./composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Copy release folders.
echo '[BUILD] Copying release folders'
cp -Rf lang $plugin_name
cp -RfL assets $plugin_name
cp -Rf lib $plugin_name
cp -Rf vendor $plugin_name
cp -Rf views $plugin_name
rm -Rf $plugin_name/assets/css/src
rm -Rf $plugin_name/assets/js/src

# Remove extra files (docs, examples,...) from 3rd party extensions
echo '[BUILD] Removing obsolete files from vendor libraries'
find $plugin_name/vendor -type f -regextype posix-egrep -iregex ".*\/*\.(markdown|md|txt)" -print0 | xargs -0 rm -f
find $plugin_name/vendor -type f -regextype posix-egrep -iregex ".*\/(readme|license|version|changes|changelog)" -print0 | xargs -0 rm -f
find $plugin_name/vendor -type d -regextype posix-egrep -iregex ".*\/(docs?|examples?|\.git)" -print0 | xargs -0 rm -rf

# Remove unit tests from 3rd party extensions
echo '[BUILD] Removing unit tests from vendor libraries'
rm -rf $plugin_name/vendor/twig/twig/test
rm -rf $plugin_name/vendor/symfony/translation/Tests
rm -rf $plugin_name/vendor/phpmailer/phpmailer/test
rm -rf $plugin_name/vendor/soundasleep/html2text/tests
rm -rf $plugin_name/vendor/mtdowling/cron-expression/tests
rm -rf $plugin_name/vendor/swiftmailer/swiftmailer/tests
rm -rf $plugin_name/vendor/cerdic/css-tidy/testing

# Remove risky files from 3rd party extensions
echo '[BUILD] Removing risky and demo files from vendor libraries'
rm -f $plugin_name/vendor/j4mie/idiorm/demo.php
rm -f $plugin_name/vendor/cerdic/css-tidy/css_optimiser.php

# Copy release files.
echo '[BUILD] Copying release files'
cp license.txt $plugin_name
cp index.php $plugin_name
cp $plugin_name.php $plugin_name
cp readme.txt $plugin_name
cp uninstall.php $plugin_name

# Add index files if they don't exist to all folders
echo '[BUILD] Adding index files to all project folders'
find $plugin_name -type d -exec touch {}/index.php \;

# Zip final release.
echo '[BUILD] Creating final release zip'
zip -r $plugin_name.zip $plugin_name

# Remove temp dir.
echo '[BUILD] Removing temp directory'
rm -rf $plugin_name

# Reinstall dev dependencies.
echo '[BUILD] Reinstalling dev dependencies'
./composer.phar install

echo '[BUILD] Build finished!'
