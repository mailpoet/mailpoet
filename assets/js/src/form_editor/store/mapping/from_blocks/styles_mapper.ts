import { has } from 'lodash';
import {
  InputBlockStyles,
  InputBlockStylesServerData,
  FontSizeDefinition,
  ColorDefinition,
} from 'form_editor/store/form_data_types';

export const mapInputBlockStyles = (styles: InputBlockStyles) => {
  const mappedStyles: InputBlockStylesServerData = {
    full_width: styles.fullWidth ? '1' : '0',
  };
  if (styles.inheritFromTheme) {
    return mappedStyles;
  }
  mappedStyles.bold = styles.bold ? '1' : '0';
  if (has(styles, 'backgroundColor') && styles.backgroundColor) {
    mappedStyles.background_color = styles.backgroundColor;
  }
  if (has(styles, 'fontSize') && styles.fontSize !== undefined) {
    mappedStyles.font_size = styles.fontSize;
  }
  if (has(styles, 'fontColor') && styles.fontColor) {
    mappedStyles.font_color = styles.fontColor;
  }
  if (has(styles, 'borderSize') && styles.borderSize !== undefined) {
    mappedStyles.border_size = styles.borderSize;
  }
  if (has(styles, 'borderRadius') && styles.borderRadius !== undefined) {
    mappedStyles.border_radius = styles.borderRadius;
  }
  if (has(styles, 'borderColor') && styles.borderColor) {
    mappedStyles.border_color = styles.borderColor;
  }
  if (has(styles, 'padding') && styles.padding !== undefined) {
    mappedStyles.padding = styles.padding;
  }
  if (has(styles, 'fontFamily') && styles.fontFamily) {
    mappedStyles.font_family = styles.fontFamily;
  }
  return mappedStyles;
};

export const mapColorSlugToValue = (
  colorDefinitions: ColorDefinition[],
  colorSlug: string,
  colorValue: string = null
): string => {
  const result = colorDefinitions.find((color) => color.slug === colorSlug);
  return result ? result.color : colorValue;
};

export const mapFontSizeSlugToValue = (
  fontSizeDefinitions: FontSizeDefinition[],
  sizeSlug: string,
  sizeValue: number = null
): number => {
  const result = fontSizeDefinitions.find((size) => size.slug === sizeSlug);
  return result ? result.size : sizeValue;
};
