const path = require('path');

module.exports = {
  stories: ['../assets/js/src/**/_stories/*.tsx'],
  webpackFinal: (config) => ({
    ...config,
    resolve: {
      ...config.resolve,
      modules: ['node_modules', '../assets/js/src'],
    },
  }),
  addons: [
    '@storybook/addon-actions',
    '@storybook/addon-links',
    'storybook-addon-performance/register',
    {
      name: '@storybook/preset-typescript',
      options: {
        tsLoaderOptions: {
          configFile: 'tsconfig.storybook.json',
        },
      },
    },
    {
      name: '@storybook/addon-storysource',
      options: {
        rule: {
          test: [/_stories\/.*\.tsx?$/],
          include: [path.resolve(__dirname, '../assets/js/src')],
        },
        loaderOptions: {
          parser: 'typescript',
        },
      },
    },
  ],
};
