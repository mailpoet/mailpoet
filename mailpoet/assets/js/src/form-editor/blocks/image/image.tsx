import '@wordpress/core-data';
import { getCoreBlockSettings } from '../core-block-settings';

export const name = 'core/image';

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
