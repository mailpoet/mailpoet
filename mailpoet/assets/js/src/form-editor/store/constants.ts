import { SETTINGS_DEFAULTS } from '@wordpress/block-editor';

export const storeName = 'mailpoet-form-editor';

export const FONT_SIZES = SETTINGS_DEFAULTS.fontSizes.map((map) => ({
  ...map,
  size: `${map.size}${Number.isNaN(Number(`${map.size}` || NaN)) ? '' : 'px'}`,
}));
