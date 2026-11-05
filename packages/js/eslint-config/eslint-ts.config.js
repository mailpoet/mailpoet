const FlatCompat = require('@eslint/eslintrc').FlatCompat;
const tsPlugin = require('@typescript-eslint/eslint-plugin');
const tsParser = require('@typescript-eslint/parser');
const airbnbConfig = require('eslint-config-airbnb');
const airbnbTsConfig = require('eslint-config-airbnb-typescript');
const prettierConfig = require('eslint-config-prettier');
const webpackResolver = require('eslint-import-resolver-webpack');
const checkFilePlugin = require('eslint-plugin-check-file');
const noOnlyTestsPlugin = require('eslint-plugin-no-only-tests');
const reactJsxRuntimeConfig = require('eslint-plugin-react/configs/jsx-runtime');
const reactHooksPlugin = require('eslint-plugin-react-hooks');
const globals = require('globals');

// fix @typescript-eslint/eslint-plugin configs to work with the new ESLint config format
const tsConfigsPath = `${__dirname}/node_modules/@typescript-eslint/eslint-plugin/dist/configs`;
const tsConfigs = Object.fromEntries(
  Object.entries(tsPlugin.configs).map(([name, config]) => [
    name,
    {
      ...config,
      extends: (config.extends ?? []).map((path) =>
        path.replace('./configs', tsConfigsPath),
      ),
    },
  ]),
);

// compat configs
const compat = new FlatCompat({ baseDirectory: __dirname });
const tsRecommendedCompatConfig = compat.config(tsConfigs.recommended);
const tsRequiringTypeCheckingCompatConfig = compat.config(
  tsConfigs['recommended-requiring-type-checking'],
);
const airbnbCompatConfig = compat.config(airbnbConfig);
const airbnbTsCompatConfig = compat.config(airbnbTsConfig);
const prettierCompatConfig = compat.config(prettierConfig);

// React plugin is already defined by airbnb config. This fixes:
//   TypeError: Key "plugins": Cannot redefine plugin "react"
delete reactJsxRuntimeConfig.plugins.react;

const KEBAB_CASE_PATTERN = '+([a-z])*([a-z0-9])*(-+([a-z0-9]))';

module.exports = [
  ...tsRecommendedCompatConfig,
  ...tsRequiringTypeCheckingCompatConfig,
  ...airbnbCompatConfig,
  ...airbnbTsCompatConfig,
  reactJsxRuntimeConfig,
  ...prettierCompatConfig,
  {
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        tsconfigRootDir: '.',
        project: ['./tsconfig.json'],
        ecmaVersion: 6,
        ecmaFeatures: {
          jsx: true,
        },
      },
      globals: {
        ...globals.browser,
      },
    },
    settings: {
      'import/resolver': { webpack: webpackResolver },
      'import/parsers': {
        // prevent "parserPath is required" error with the new ESLint config format
        '@typescript-eslint/parser': ['.ts', '.tsx', '.js', '.jsx'],
      },
    },
    plugins: {
      'react-hooks': reactHooksPlugin,
      'check-file': checkFilePlugin,
      'no-only-tests': noOnlyTestsPlugin,
    },
    rules: {
      // PropTypes
      'react/prop-types': 0,
      'react/jsx-props-no-spreading': 0,
      'react/require-default-props': 0,
      // Hooks
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      // Exceptions
      '@typescript-eslint/no-explicit-any': 'error', // make it an error instead of warning - we treat them the same, this is more visible
      'no-void': 0, // can conflict with @typescript-eslint/no-floating-promises
      'react/jsx-filename-extension': 0,
      'class-methods-use-this': 0,
      '@typescript-eslint/no-unsafe-member-access': 0, // this needs to be off until we have typed assignments :(
      '@typescript-eslint/no-unsafe-call': 0, // this needs to be off until we have typed assignments :(
      '@typescript-eslint/no-unsafe-assignment': 0, // this needs to be off until we have typed assignments :(
      'import/extensions': 0, // we wouldn't be able to import jQuery without this line
      'import/no-named-as-default': 0, // we use named default exports at the moment
      'import/prefer-default-export': 0, // we want to stop using default exports and start using named exports
      'react/destructuring-assignment': 0, // that would be too many changes to fix this one
      'prefer-destructuring': 0, // that would be too many changes to fix this one
      'jsx-a11y/label-has-for': [
        2,
        {
          required: { some: ['nesting', 'id'] }, // some of our labels are hidden and we cannot nest those
        },
      ],
      'jsx-a11y/anchor-is-valid': 0, // cannot fix this one, it would break wprdpress themes
      'jsx-a11y/label-has-associated-control': [
        2,
        {
          either: 'either', // control has to be either nested or associated via htmlFor
        },
      ],
      'import/no-default-export': 1, // no default exports
      '@typescript-eslint/no-misused-promises': [
        'error',
        {
          checksVoidReturn: {
            attributes: false, // it is OK to pass an async function to JSX attributes
          },
        },
      ],
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

  // allow Storybook stories to use dev dependencies and default exports
  {
    files: ['**/_stories/*.tsx'],
    rules: {
      'import/no-extraneous-dependencies': ['error', { devDependencies: true }],
      'import/no-default-export': 0,
    },
  },
];
