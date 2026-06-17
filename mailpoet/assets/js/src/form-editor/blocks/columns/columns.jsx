import { getCoreBlockSettings } from '../core-block-settings';

export const name = 'core/columns';

const settingsReset = {
  name,
  examples: null,
  deprecated: null,
  save: () => null,
};
const settings = getCoreBlockSettings(name, settingsReset, {
  anchor: false,
});

export { settings };
