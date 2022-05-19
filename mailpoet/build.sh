#!/bin/bash -e

echo '[BUILD] Generating translations .pot file'
./do translations:build

plugin_name='mailpoet'

# Remove previous build.
echo '[BUILD] Removing previous build'
rm -f $plugin_name.zip

# Create temp dir.
echo '[BUILD] Creating temporary directory'
rm -rf $plugin_name
mkdir $plugin_name

# Production assets.
echo '[BUILD] Generating production CSS and JS assets'
rm -rf node_modules
npm ci --prefer-offline
./do compile:all --env production

# Dependency injection container cache.
echo '[BUILD] Building DI Container cache'
./tools/vendor/composer.phar install
./do container:dump

# Generate Doctrine cache
echo '[BUILD] Generating Doctrine Cache'
./do doctrine:generate-cache

# Generate Twig cache
echo '[BUILD] Twig templates cache'
./do twig:generate-cache

# Backup dev libraries
echo '[BUILD] Backup dev dependencies'
if [ -d 'vendor' ]; then
	mv vendor vendor-backup
fi
if [ -d 'vendor-prefixed' ]; then
	mv vendor-prefixed vendor-prefixed-backup
fi

# Production libraries.
echo '[BUILD] Fetching production libraries'
mkdir vendor-prefixed
./tools/vendor/composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-scripts

echo '[BUILD] Fetching prefixed production libraries'
./tools/vendor/composer.phar install --no-dev --prefer-dist --working-dir=./prefixer/

# Remove Doctrinne Annotations (no need since generated metadata are packed)
# Should be removed before `dump-autoload` to not include the annotations classes on the autoloader.
rm -rf vendor-prefixed/doctrine/annotations

# Remove DI Container files used for container dump (no need since generated metadata are packed)
# Should be removed before `dump-autoload` to not include these classes in the autoloader.
echo '[BUILD] Removing DI Container development dependencies'
rm -rf vendor-prefixed/symfony/dependency-injection/Compiler
rm -rf vendor-prefixed/symfony/dependency-injection/Config
rm -rf vendor-prefixed/symfony/dependency-injection/Dumper
rm -rf vendor-prefixed/symfony/dependency-injection/Loader
rm -rf vendor-prefixed/symfony/dependency-injection/LazyProxy
rm -rf vendor-prefixed/symfony/dependency-injection/Extension

./tools/vendor/composer.phar dump-autoload

# Copy release folders.
echo '[BUILD] Copying release folders'
cp -Rf lang $plugin_name
cp -RfL assets $plugin_name
cp -Rf generated $plugin_name
cp -Rf lib $plugin_name
cp -Rf lib-3rd-party $plugin_name
cp -Rf vendor $plugin_name
cp -Rf vendor-prefixed $plugin_name
cp -Rf views $plugin_name
rm -Rf $plugin_name/assets/css/src
rm -Rf $plugin_name/assets/js/src

# Remove generated PHP files after they were copied for release
rm -Rf generated/*.php

# Remove extra files (docs, examples,...) from 3rd party extensions
unameString=`uname`
if [ "$unameString" = 'Darwin' ]; then
   findPreArgs=' -E '
   findMidArgs=''
else
   findPreArgs=''
   findMidArgs=' -regextype posix-egrep '
fi
findDestinations="$plugin_name/vendor $plugin_name/vendor-prefixed"

echo '[BUILD] Removing obsolete files from vendor libraries'
find $findPreArgs $findDestinations -type f $findMidArgs -iregex ".*\/*\.(markdown|md|txt)" -print0 | xargs -0 rm -f
find $findPreArgs $findDestinations -type f $findMidArgs -iregex ".*\/(readme|license|version|changes|changelog|composer\.json|composer\.lock|phpunit\.xml.*|doxyfile)" -print0 | xargs -0 rm -f
find $findPreArgs $findDestinations -type f $findMidArgs -iregex ".*\/(\.editorconfig|\.git.*|\.travis.yml|\.php_cs.*)" -print0 | xargs -0 rm -f
find $findPreArgs $findDestinations -type d $findMidArgs -iregex ".*\/(docs?|examples?|\.git)" -print0 | xargs -0 rm -rf

# Remove all .gitignore files
find $findPreArgs $plugin_name -type f $findMidArgs -iregex ".*\/\.gitignore" -print0 | xargs -0 rm -f

# Remove Tracy panels
rm -rf $plugin_name/lib/Tracy

# Remove unit tests from 3rd party extensions
echo '[BUILD] Removing unit tests from vendor libraries'
rm -rf $plugin_name/vendor-prefixed/cerdic/css-tidy/COPYING
rm -rf $plugin_name/vendor-prefixed/cerdic/css-tidy/NEWS
rm -rf $plugin_name/vendor-prefixed/cerdic/css-tidy/testing
rm -rf $plugin_name/vendor/mtdowling/cron-expression/tests
rm -rf $plugin_name/vendor/phpmailer/phpmailer/test
rm -rf $plugin_name/vendor-prefixed/psr/log/Psr/Log/Test
rm -rf $plugin_name/vendor-prefixed/sabberworm/php-css-parser/tests
rm -rf $plugin_name/vendor/soundasleep/html2text/tests
rm -rf $plugin_name/vendor-prefixed/swiftmailer/swiftmailer/tests
rm -rf $plugin_name/vendor-prefixed/symfony/service-contracts/Tests
rm -rf $plugin_name/vendor-prefixed/symfony/translation/Tests
rm -rf $plugin_name/vendor-prefixed/symfony/translation-contracts/Tests

# Remove risky files from 3rd party extensions
echo '[BUILD] Removing risky and demo files from vendor libraries'
rm -f $plugin_name/vendor-prefixed/cerdic/css-tidy/css_optimiser.php
rm -rf $plugin_name/vendor-prefixed/gregwar/captcha/demo
rm -rf $plugin_name/vendor-prefixed/gregwar/captcha/src/Gregwar/Captcha/Font/captcha4.ttf # big font
rm -rf $plugin_name/vendor-prefixed/cerdic/css-tidy/bin
rm -f $plugin_name/vendor-prefixed/egulias/email-validator/psalm*.xml

# Copy release files.
echo '[BUILD] Copying release files'
cp license.txt $plugin_name
cp index.php $plugin_name
cp $plugin_name-cron.php $plugin_name
cp $plugin_name.php $plugin_name
cp mailpoet_initializer.php $plugin_name
cp readme.txt $plugin_name

# Prefix all PHP files with "<?php if (!defined('ABSPATH')) exit; ?>"
echo '[BUILD] Adding ABSPATH ensuring prefix to all PHP files (to avoid path disclosure)'
php "$(dirname "$0")"/tasks/fix-full-path-disclosure.php $plugin_name

# Add index.php files if they don't exist to all folders
echo '[BUILD] Adding index.php files to all project folders (to avoid directory listing disclosure)'
find $plugin_name -type d -print0 | while read -d $'\0' dir; do
  if [ ! -f "$dir/Index.php" ]; then
    touch "$dir/index.php"
  fi
done

# Strip whitespaces and comments from PHP files in vendor and vendor prefixed folders
echo '[BUILD] Strip whitespaces and comments from PHP files in vendor folder'
php "$(dirname "$0")"/tasks/strip-whitespaces.php $plugin_name/vendor
php "$(dirname "$0")"/tasks/strip-whitespaces.php $plugin_name/vendor-prefixed

# Zip final release.
echo '[BUILD] Creating final release zip'
zip -r $plugin_name.zip $plugin_name

# Remove temp dir.
echo '[BUILD] Removing temp directory'
rm -rf $plugin_name

# Restore dev dependencies.
echo '[BUILD] Restoring dev dependencies'
if [ -d 'vendor-backup' ]; then
	rm -rf vendor
	mv vendor-backup vendor
fi
if [ -d 'vendor-prefixed-backup' ]; then
	rm -rf vendor-prefixed
	mv vendor-prefixed-backup vendor-prefixed
fi

echo '[BUILD] Build finished!'
