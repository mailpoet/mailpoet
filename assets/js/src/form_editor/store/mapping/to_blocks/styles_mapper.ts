import { has } from 'lodash';
import { InputBlockStyles } from 'form_editor/store/form_data_types';

export const defaultBlockStyles: InputBlockStyles = {
  fullWidth: true,
  inheritFromTheme: true,
};

const backwardCompatibleBlockStyles: InputBlockStyles = {
  fullWidth: false,
  inheritFromTheme: true,
};

export const mapInputBlockStyles = (styles) => {
  if (!styles) {
    return backwardCompatibleBlockStyles;
  }
  const mappedStyles: InputBlockStyles = {
    fullWidth: styles.full_width === '1' || styles.full_width === true,
    inheritFromTheme: !has(styles, 'bold'), // Detect if styles inherit from theme by checking if bold param is present
  };
  if (mappedStyles.inheritFromTheme) {
    return mappedStyles;
  }
  mappedStyles.bold = styles.bold === '1' || styles.bold === true;
  if (has(styles, 'background_color') && styles.background_color) {
    mappedStyles.backgroundColor = styles.background_color;
  }
  if (has(styles, 'border_size') && styles.border_size !== undefined) {
    mappedStyles.borderSize = parseInt(styles.border_size, 10);
  }
  if (has(styles, 'font_size') && styles.font_size !== undefined) {
    mappedStyles.fontSize = parseInt(styles.font_size, 10);
  }
  if (has(styles, 'font_color') && styles.font_color) {
    mappedStyles.fontColor = styles.font_color;
  }
  if (has(styles, 'border_radius') && styles.border_radius !== undefined) {
    mappedStyles.borderRadius = parseInt(styles.border_radius, 10);
  }
  if (has(styles, 'border_color') && styles.border_color) {
    mappedStyles.borderColor = styles.border_color;
  }
  if (has(styles, 'padding') && styles.padding !== undefined) {
    mappedStyles.padding = parseInt(styles.padding, 10);
  }
  if (has(styles, 'font_family') && styles.font_family) {
    mappedStyles.fontFamily = styles.font_family;
  }
  return mappedStyles;
};
