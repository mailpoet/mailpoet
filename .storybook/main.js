module.exports = {
  stories: ['../assets/js/src/**/_stories/*.tsx'],
  addons: [
    '@storybook/addon-actions',
    '@storybook/addon-links',
    '@storybook/preset-typescript',
    'storybook-addon-performance/register',
  ],
};
