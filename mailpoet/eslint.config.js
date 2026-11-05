const es5Config = require('@mailpoet/eslint-config/eslint-es5.config');
const es6Config = require('@mailpoet/eslint-config/eslint-es6.config');
const esTsConfig = require('@mailpoet/eslint-config/eslint-ts.config');
const globals = require('@mailpoet/eslint-config/globals');

module.exports = [
  {
    ignores: [
      'assets/js/src/vendor/**',
      // Generated from node_modules by tasks/npm_post_install.sh
      'assets/js/src/newsletter-editor/behaviors/tinymce-icons.js',
      'tests/javascript-newsletter-editor/testBundles/**',
    ],
  },
  ...es5Config.map((config) => ({
    ...config,
    files: [
      'assets/js/src/**/*.js',
      'tests/javascript-newsletter-editor/**/*.js',
    ],
  })),
  ...es6Config.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.jsx', 'tests/javascript/**/*.js'],
  })),
  ...esTsConfig.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.{ts,tsx}'],
  })),
  ...esTsConfig.map((config) => ({
    ...config,
    files: ['tests/javascript/**/*.ts'],
    languageOptions: {
      ...config.languageOptions,
      globals: {
        ...config.languageOptions?.globals,
        ...globals.mocha,
      },
    },
  })),

  // ES5 config overrides
  {
    files: ['tests/javascript-newsletter-editor/**/*.js'],
    languageOptions: {
      globals: {
        ...globals.mocha,
      },
    },
    rules: {
      'func-names': 0,
    },
  },

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

  // TS config overrides
  {
    files: ['assets/js/src/**/*.{ts,tsx}'],
    rules: {
      'react/no-unstable-nested-components': ['error', { allowAsProps: true }],
      'react/jsx-no-useless-fragment': ['error', { allowExpressions: true }],
    },
  },
];
