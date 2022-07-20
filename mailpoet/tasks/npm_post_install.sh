#!/bin/bash

echo "/* eslint-disable */
" > $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

echo "import tinymce from 'tinymce/tinymce';
" >> $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

cat $INIT_CWD/node_modules/tinymce/icons/default/icons.min.js >> $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

# Replace deprecated jQuery methods in the spectrum-colorpicker dependency
# Remove this when a patch is included in a package update
git apply $INIT_CWD/tasks/patches/spectrum-replace-jquery-deprecated.patch || true
