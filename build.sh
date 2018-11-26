#!/bin/sh -e

# Translations (npm install & composer install need to be run before)
echo '[BUILD] Generating translations'
./do makepot
./do packtranslations

plugin_name='mailpoet'

# Remove previous build.
echo '[BUILD] Removing previous build'
test -e $plugin_name.zip && rm $plugin_name.zip

# Create temp dir.
echo '[BUILD] Creating temporary directory'
test -d $plugin_name && rm -rf $plugin_name
mkdir $plugin_name

# Production assets.
echo '[BUILD] Generating production CSS and JS assets'
test -d node_modules && rm -rf node_modules
npm install
./do compile:all --env production

# Dependency injection container cache.
echo '[BUILD] Building DI Container cache'
./composer.phar install
./do container:dump

# Production libraries.
echo '[BUILD] Fetching production libraries'
test -d vendor && rm -rf vendor
test -d vendor-prefixed && rm -rf vendor-prefixed
./composer.phar install --no-dev --prefer-dist --optimize-autoloader --no-scripts

echo '[BUILD] Fetching mozart managed production libraries'
./composer.phar install --no-dev --prefer-dist --working-dir=./mozart/

# Copy release folders.
echo '[BUILD] Copying release folders'
cp -Rf lang $plugin_name
cp -RfL assets $plugin_name
cp -Rf lib $plugin_name
cp -Rf vendor $plugin_name
cp -Rf vendor-prefixed $plugin_name
cp -Rf views $plugin_name
rm -Rf $plugin_name/assets/css/src
rm -Rf $plugin_name/assets/js/src
rm -Rf $plugin_name/lang/*.po

# Remove extra files (docs, examples,...) from 3rd party extensions
unameString=`uname`
if [[ "$unameString" == 'Darwin' ]]; then
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
rm -f $plugin_name/assets/js/lib/tinymce/package.json

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
