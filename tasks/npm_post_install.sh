#!/bin/bash

echo "/* eslint-disable */
" > $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

echo "import tinymce from 'tinymce/tinymce';
" >> $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

cat $INIT_CWD/node_modules/tinymce/icons/default/icons.min.js >> $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

# Replace deprecated jQuery methods in the spectrum-colorpicker dependency
# Remove this when a patch is included in a package update
git apply $INIT_CWD/tasks/patches/spectrum-replace-jquery-deprecated.patch || true

# Replace deprecated jQuery methods in the parsley dependency
# Remove this when a patch is included in a package update.
# Note: deferred.pipe() fix is not implemented yet, see https://github.com/guillaumepotier/Parsley.js/pull/1347
sed -i -- "s/_focusedField\.focus()/_focusedField\.trigger('focus')/g" node_modules/parsleyjs/dist/parsley*.js

# The package @wordpress/block-editor contains invalid CSS file. We need to fix it due to sass import.
# Github issue: https://github.com/WordPress/gutenberg/issues/32181
sed -i -- "s/box-shadow: 0 0 0 2px-1px #007cba;/box-shadow: 0 0 0 calc(2px-1px) #007cba;/g" node_modules/@wordpress/block-editor/build-style/style.css
sed -i -- "s/box-shadow: 0 0 0 var(--wp-admin-border-width-focus)-1px var(--wp-admin-theme-color);/box-shadow: 0 0 0 calc(var(--wp-admin-border-width-focus)-1px) var(--wp-admin-theme-color);/g" node_modules/@wordpress/block-editor/build-style/style.css

# Fix strict mode issues in Backbone.Supermodel
sed -i -- "s/lastKeyIndex = keyPath.length-1;/var lastKeyIndex = keyPath.length-1;/g" node_modules/backbone.supermodel/build/backbone.supermodel.js
sed -i -- "s/key = keyPath\[i\];/var key = keyPath\[i\];/g" node_modules/backbone.supermodel/build/backbone.supermodel.js
