#!/bin/sh -e

# Translations (npm ci & composer install need to be run before)
echo '[BUILD] Generating translations'
./do translations:build
./do translations:pack

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
./composer.phar install
./do container:dump

# Generate Doctrine metadata
echo '[BUILD] Generating Doctrine Metadata'
./do doctrine:generate-metadata

# Generate Doctrine proxies
echo '[BUILD] Generating Doctrine Proxies'
./do doctrine:generate-proxies

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
./composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-scripts

echo '[BUILD] Fetching prefixed production libraries'
./composer.phar install --prefer-dist --working-dir=./prefixer/
./composer.phar dump-autoload

# Copy release folders.
echo '[BUILD] Copying release folders'
cp -Rf lang $plugin_name
cp -RfL assets $plugin_name
cp -Rf generated $plugin_name
cp -Rf lib $plugin_name
cp -Rf vendor $plugin_name
cp -Rf vendor-prefixed $plugin_name
cp -Rf views $plugin_name
rm -Rf $plugin_name/assets/css/src
rm -Rf $plugin_name/assets/js/src
rm -Rf $plugin_name/lang/*.po

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

# Remove Tracy panels
rm -rf $plugin_name/lib/Tracy

# Remove unit tests from 3rd party extensions
echo '[BUILD] Removing unit tests from vendor libraries'
rm -rf $plugin_name/vendor/cerdic/css-tidy/COPYING
rm -rf $plugin_name/vendor/cerdic/css-tidy/NEWS
rm -rf $plugin_name/vendor/cerdic/css-tidy/testing
rm -rf $plugin_name/vendor/mtdowling/cron-expression/tests
rm -rf $plugin_name/vendor/nesbot/Carbon/Laravel
rm -rf $plugin_name/vendor/phpmailer/phpmailer/test
rm -rf $plugin_name/vendor/psr/log/Psr/Log/Test
rm -rf $plugin_name/vendor/sabberworm/php-css-parser/tests
rm -rf $plugin_name/vendor/soundasleep/html2text/tests
rm -rf $plugin_name/vendor/swiftmailer/swiftmailer/tests
rm -rf $plugin_name/vendor/symfony/translation/Tests
rm -rf $plugin_name/vendor/twig/twig/test

# Remove risky files from 3rd party extensions
echo '[BUILD] Removing risky and demo files from vendor libraries'
rm -f $plugin_name/vendor/j4mie/idiorm/demo.php
rm -f $plugin_name/vendor/cerdic/css-tidy/css_optimiser.php
rm -rf $plugin_name/vendor-prefixed/gregwar/captcha/demo
rm -rf $plugin_name/vendor-prefixed/gregwar/captcha/src/Gregwar/Captcha/Font/captcha4.ttf # big font
rm -f $plugin_name/assets/js/lib/tinymce/package.json

# Remove unused TinyMCE files
echo '[BUILD] Removing unused TinyMCE files'
rm -f $plugin_name/assets/js/lib/tinymce/bower.json
rm -f $plugin_name/assets/js/lib/tinymce/changelog.txt
rm -f $plugin_name/assets/js/lib/tinymce/composer.json
rm -f $plugin_name/assets/js/lib/tinymce/jquery.tinymce.js
rm -f $plugin_name/assets/js/lib/tinymce/license.txt
rm -f $plugin_name/assets/js/lib/tinymce/package.json
rm -f $plugin_name/assets/js/lib/tinymce/readme.md
rm -f $plugin_name/assets/js/lib/tinymce/tinymce.js
rm -f $plugin_name/assets/js/lib/tinymce/tinymce.jquery.js
rm -f $plugin_name/assets/js/lib/tinymce/tinymce.jquery.min.js

# Remove all TinyMCE plugins except code, link, lists, paste, textcolor, and colorpicker
find $findPreArgs $plugin_name/assets/js/lib/tinymce/plugins -mindepth 1 -type d $findMidArgs -not -iregex ".*\/(code|link|lists|paste|textcolor|colorpicker)" -print0 | xargs -0 rm -rf

# Remove all non-minimized TinyMCE plugin & theme files
rm -rf $plugin_name/assets/js/lib/tinymce/plugins/*/plugin.js
rm -rf $plugin_name/assets/js/lib/tinymce/themes/*/theme.js

# Copy release files.
echo '[BUILD] Copying release files'
cp license.txt $plugin_name
cp index.php $plugin_name
cp $plugin_name-cron.php $plugin_name
cp $plugin_name.php $plugin_name
cp mailpoet_initializer.php $plugin_name
cp readme.txt $plugin_name
cp uninstall.php $plugin_name

# Add index files if they don't exist to all folders
echo '[BUILD] Adding index files to all project folders'
find $plugin_name -type d -print0 | while read -d $'\0' dir
do
  if [ ! -f "$dir/Index.php" ]; then
    touch "$dir/index.php"
  fi
done

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
