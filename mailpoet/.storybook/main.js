const path = require('path');

const modulesDir = path.join(__dirname, '../node_modules');
console.log('NODE', modulesDir);
// Workaround for Emotion 11
// https://github.com/storybookjs/storybook/pull/13300#issuecomment-783268111
const updateEmotionAliases = (config) => ({
  ...config,
  resolve: {
    ...config.resolve,
    alias: {
      ...config.resolve.alias,
      '@emotion/core': path.join(modulesDir, '@emotion/react'),
      '@emotion/styled': path.join(modulesDir, '@emotion/styled'),
      '@emotion/styled-base': path.join(modulesDir, '@emotion/styled'),
      'emotion-theming': path.join(modulesDir, '@emotion/react'),
    },
  },
});

module.exports = {
  core: {
    builder: 'webpack5',
  },
  stories: ['../assets/js/src/**/_stories/*.tsx'],
  webpackFinal: (config) => {
    config.resolve.modules = ['node_modules', '../assets/js/src'];
    return updateEmotionAliases(config);
  },
  managerWebpack: updateEmotionAliases,
  addons: [
    '@storybook/addon-actions',
    '@storybook/addon-links',
    'storybook-addon-performance/register',
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
