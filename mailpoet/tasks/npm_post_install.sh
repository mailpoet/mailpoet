#!/bin/bash

# Resolve node_modules from workspace root (pnpm hoists packages there)
NODE_MODULES=$(cd "$INIT_CWD" && node -e "console.log(require.resolve('tinymce/package.json').replace('/package.json',''))" 2>/dev/null)
REACT_DATES=$(cd "$INIT_CWD" && node -e "console.log(require.resolve('react-dates/package.json').replace('/package.json',''))" 2>/dev/null)

echo "/* eslint-disable */
" > $INIT_CWD/assets/js/src/newsletter-editor/behaviors/tinymce-icons.js

echo "import tinymce from 'tinymce/tinymce';
" >> $INIT_CWD/assets/js/src/newsletter-editor/behaviors/tinymce-icons.js

cat "$NODE_MODULES/icons/default/icons.min.js" >> $INIT_CWD/assets/js/src/newsletter-editor/behaviors/tinymce-icons.js

# fix SCSS file being exposed as CSS file so it can be imported correctly
cp "$REACT_DATES/lib/css/_datepicker.css" "$REACT_DATES/lib/css/_datepicker.scss"
