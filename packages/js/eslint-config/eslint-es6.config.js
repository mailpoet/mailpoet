const babelParser = require('@babel/eslint-parser');
const FlatCompat = require('@eslint/eslintrc').FlatCompat;
const airbnbConfig = require('eslint-config-airbnb');
const prettierConfig = require('eslint-config-prettier');
const webpackResolver = require('eslint-import-resolver-webpack');
const checkFilePlugin = require('eslint-plugin-check-file');
const noOnlyTestsPlugin = require('eslint-plugin-no-only-tests');
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

const KEBAB_CASE_PATTERN = '+([a-z])*([a-z0-9])*(-+([a-z0-9]))';

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
      'check-file': checkFilePlugin,
      'no-only-tests': noOnlyTestsPlugin,
    },
    rules: {
      // Hooks
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      // Exceptions
      'no-only-tests/no-only-tests': 2,
      'class-methods-use-this': 0,
      'react/jsx-props-no-spreading': 0,
      'react/require-default-props': 0, // deprecated in react 18.3.1
      'import/extensions': 0, // we wouldn't be able to import jQuery without this line
      'import/prefer-default-export': 0, // we want to stop using default exports and start using named exports
      'import/no-default-export': 1, // no default exports
      'check-file/filename-naming-convention': [
        'error',
        { '**/*.*': 'KEBAB_CASE' },
        { ignoreMiddleExtensions: true },
      ],
      'check-file/folder-naming-convention': [
        'error',
        { '**/': `@(${KEBAB_CASE_PATTERN}|_stories|_storybook)` },
      ],
    },
  },
];
