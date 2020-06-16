import * as paragraph from '@wordpress/block-library/build-module/paragraph/index.js';

export const name = 'core/paragraph';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};
const settings = {
  ...paragraph.metadata,
  ...paragraph.settings,
  ...settingsReset,
  category: 'design',
  supports: {
    ...paragraph.settings.supports,
    html: false,
  },
};

export { settings };
