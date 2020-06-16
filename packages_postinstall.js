/**
 * This is a temporary fix of a bug in @wordpress/block-editor@4.1.0 package.
 * It is already fixed and merged in master https://github.com/WordPress/gutenberg/commit/1e43db126e33e13173f9af9113cb9f39f9be7f1f
 * We can remove this fix after upgrading the package to a version newer than 4.1.0
 */
const fs = require('fs');
const fileToFix = './node_modules/@wordpress/block-editor/build-module/components/button-block-appender/index.js';
fs.readFile(fileToFix, 'utf8', (err,data) => {
  if (err) {
    return console.log(err);
  }
  var result = data.replace(/" ", inserterButton, " "/g, 'inserterButton');

  fs.writeFile(fileToFix, result, 'utf8', (err) => {
    if (err) return console.log(err);
  });
});
