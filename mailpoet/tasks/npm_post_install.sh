#!/bin/bash

echo "/* eslint-disable */
" > $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

echo "import tinymce from 'tinymce/tinymce';
" >> $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

cat $INIT_CWD/node_modules/tinymce/icons/default/icons.min.js >> $INIT_CWD/assets/js/src/newsletter_editor/behaviors/tinymce_icons.js

# fix SCSS file being exposed as CSS file so it can be imported correctly
cp $INIT_CWD/node_modules/react-dates/lib/css/_datepicker.css $INIT_CWD/node_modules/react-dates/lib/css/_datepicker.scss
