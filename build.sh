#!/bin/sh

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
./composer.phar install --no-dev

# Translations
./do makepot

# Copy release folders.
cp -Rf lang $plugin_name
cp -RfL assets $plugin_name
cp -Rf lib $plugin_name
cp -Rf vendor $plugin_name
cp -Rf views $plugin_name
rm -Rf $plugin_name/assets/css/src
rm -Rf $plugin_name/assets/js/src

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
