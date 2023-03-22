module.exports = {
  extends: ['airbnb', 'plugin:react/jsx-runtime', 'prettier'],
  env: {
    browser: true,
  },
  parser: '@babel/eslint-parser',
  parserOptions: {
    ecmaVersion: 6,
    ecmaFeatures: {
      jsx: true,
    },
  },
  plugins: ['react-hooks'],
  settings: {
    'import/resolver': 'webpack',
  },
  rules: {
    'class-methods-use-this': 0,
    // React hooks rules
    'react-hooks/rules-of-hooks': 'error',
    'react-hooks/exhaustive-deps': 'warn',
    'import/prefer-default-export': 0, // we want to stop using default exports and start using named exports
    // Exceptions
    'import/extensions': 0, // we wouldn't be able to import jQuery without this line
    'jsx-a11y/anchor-is-valid': [
      'error',
      {
        components: ['Link'],
        specialLink: ['to'],
      },
    ],
    'import/no-default-export': 1, // no default exports
  },
};
