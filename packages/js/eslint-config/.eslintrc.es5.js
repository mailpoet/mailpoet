module.exports = {
  extends: ['airbnb/legacy', 'prettier'],
  env: {
    amd: true,
    browser: true,
  },
  plugins: ['eslint-plugin-import'],
  parser: '@babel/eslint-parser',
  parserOptions: {
    ecmaVersion: 6,
    sourceType: 'module',
  },
  rules: {
    'import/prefer-default-export': 0, // we want to stop using default exports and start using named exports
    'no-underscore-dangle': 0, // Backbone uses underscores, we cannot remove them
    'import/no-default-export': 1, // no default exports
  },
};
