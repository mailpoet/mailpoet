import '@wordpress/core-data';
import * as image from '@wordpress/block-library/build-module/image/index.js';

export const name = 'core/image';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};
const settings = {
  ...image.metadata,
  ...image.settings,
  ...settingsReset,
  category: 'design',
  supports: {
    ...image.settings.supports,
    html: false,
  },
};
export { settings };
