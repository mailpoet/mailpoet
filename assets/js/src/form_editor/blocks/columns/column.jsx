import * as column from '@wordpress/block-library/build-module/column/index.js';

export const name = 'core/column';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};
const settings = {
  ...column.metadata,
  ...column.settings,
  ...settingsReset,
  category: 'design',
  supports: {
    ...column.metadata.supports,
    anchor: false,
  },
};
export { settings };
