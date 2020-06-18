import * as heading from '@wordpress/block-library/build-module/heading/index.js';

export const name = 'core/heading';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};

const settings = {
  ...heading.metadata,
  ...heading.settings,
  ...settingsReset,
  category: 'design',
  supports: {
    ...heading.metadata.supports,
    html: false,
    __experimentalFontSize: false, // Temporarily turned off until we add support on frontend
    __experimentalLineHeight: false, // Temporarily turned off until we add support on frontend
  },
};

export { settings };
