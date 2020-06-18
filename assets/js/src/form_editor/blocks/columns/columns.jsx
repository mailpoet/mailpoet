import * as columns from '@wordpress/block-library/build-module/columns/index.js';

export const name = 'core/columns';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};
const settings = {
  ...columns.metadata,
  ...columns.settings,
  ...settingsReset,
  category: 'design',
};
// Turn off Gradient support in columns
// eslint-disable-next-line no-underscore-dangle
settings.supports.__experimentalColor.gradients = false;
export { settings };
