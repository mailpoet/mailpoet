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

  // ES6 config overrides
  {
    files: ['assets/js/src/**/*.jsx', 'tests/javascript/**/*.js'],
    rules: {
      'no-script-url': 0,
      'react/destructuring-assignment': 0, // that would be too many changes to fix this one
      'prefer-destructuring': 0, // that would be too many changes to fix this one
      'jsx-a11y/label-has-for': [
        2,
        {
          required: { some: ['nesting', 'id'] }, // some of our labels are hidden and we cannot nest those
        },
      ],
      'jsx-a11y/anchor-is-valid': 0, // cannot fix this one, it would break wordpress themes
      'jsx-a11y/label-has-associated-control': [
        2,
        {
          either: 'either', // control has to be either nested or associated via htmlFor
        },
      ],
    },
  },
];
