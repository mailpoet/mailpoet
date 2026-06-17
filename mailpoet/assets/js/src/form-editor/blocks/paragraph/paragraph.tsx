import { getCoreBlockSettings } from '../core-block-settings';

export const name = 'core/paragraph';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: (): null => null,
};

const settings = getCoreBlockSettings(name, settingsReset, {
  html: false,
  anchor: false,
});

export { settings };
