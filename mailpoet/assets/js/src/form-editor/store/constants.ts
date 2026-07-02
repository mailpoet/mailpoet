import { SETTINGS_DEFAULTS } from '@wordpress/block-editor';
import type { ColorDefinition } from './form-data-types';
import { getFormEditorColorPalette } from './color-palette';

export const storeName = 'mailpoet-form-editor';

export const FONT_SIZES = SETTINGS_DEFAULTS.fontSizes.map((map) => ({
  ...map,
  size: `${map.size}${Number.isNaN(Number(`${map.size}` || NaN)) ? '' : 'px'}`,
}));

const getThemeColorPalette = (): ColorDefinition[] => {
  if (typeof window === 'undefined') {
    return [];
  }
  const formEditorWindow = window as Window & {
    mailpoet_form_editor_color_palette?: ColorDefinition[];
  };
  if (!Array.isArray(formEditorWindow.mailpoet_form_editor_color_palette)) {
    return [];
  }
  return formEditorWindow.mailpoet_form_editor_color_palette;
};

export const THEME_COLOR_PALETTE = getThemeColorPalette();
export const FORM_EDITOR_COLOR_PALETTE = getFormEditorColorPalette(
  THEME_COLOR_PALETTE,
  SETTINGS_DEFAULTS.colors,
);
export { getFormEditorColorPalette };
