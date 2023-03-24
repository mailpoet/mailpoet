const babelParser = require('@babel/eslint-parser');
const FlatCompat = require('@eslint/eslintrc').FlatCompat;
const airbnbConfig = require('eslint-config-airbnb');
const prettierConfig = require('eslint-config-prettier');
const webpackResolver = require('eslint-import-resolver-webpack');
const reactJsxRuntimeConfig = require('eslint-plugin-react/configs/jsx-runtime');
const reactHooksPlugin = require('eslint-plugin-react-hooks');
const globals = require('globals');

// compat configs
const compat = new FlatCompat({ baseDirectory: __dirname });
const airbnbCompatConfig = compat.config(airbnbConfig);
const prettierCompatConfig = compat.config(prettierConfig);

// React plugin is already defined by airbnb config. This fixes:
//   TypeError: Key "plugins": Cannot redefine plugin "react"
delete reactJsxRuntimeConfig.plugins.react;

module.exports = [
  ...airbnbCompatConfig,
  reactJsxRuntimeConfig,
  ...prettierCompatConfig,
  {
    languageOptions: {
      parser: babelParser,
      globals: {
        ...globals.browser,
      },
    },
    settings: {
      'import/resolver': { webpack: webpackResolver },
    },
    plugins: {
      'react-hooks': reactHooksPlugin,
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
  },
];
