const es5Config = require('@mailpoet/eslint-config/eslint-es5.config');
const es6Config = require('@mailpoet/eslint-config/eslint-es6.config');
const esTsConfig = require('@mailpoet/eslint-config/eslint-ts.config');
const esTestsNewsletterEditorConfig = require('@mailpoet/eslint-config/eslint-tests-newsletter-editor.config');

module.exports = [
  {
    ignores: [
      'assets/js/src/vendor/**',
      'tests/javascript_newsletter_editor/testBundles/**',
    ],
  },
  ...es5Config.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.js'],
  })),
  ...es6Config.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.jsx', 'tests/javascript/**/*.js'],
  })),
  ...esTsConfig.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.{ts,tsx}'],
  })),
  ...esTestsNewsletterEditorConfig.map((config) => ({
    ...config,
    files: ['tests/javascript_newsletter_editor/**/*.js'],
  })),
];
