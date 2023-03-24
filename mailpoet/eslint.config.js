const es5Config = require('@mailpoet/eslint-config/eslint-es5.config');
const es6Config = require('@mailpoet/eslint-config/eslint-es6.config');
const esTsConfig = require('@mailpoet/eslint-config/eslint-ts.config');

module.exports = [
  {
    ignores: ['assets/js/src/vendor/**'],
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
];
