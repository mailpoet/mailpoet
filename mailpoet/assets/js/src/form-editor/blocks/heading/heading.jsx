import { getCoreBlockSettings } from '../core-block-settings';

export const name = 'core/heading';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};

const settings = getCoreBlockSettings(name, settingsReset, {
  html: false,
});

export { settings };
