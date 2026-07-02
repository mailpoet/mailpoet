import type { ColorDefinition } from './form-data-types';

export const getFormEditorColorPalette = (
  themePalette: ColorDefinition[],
  defaultPalette: ColorDefinition[],
): ColorDefinition[] => [
  ...themePalette,
  ...defaultPalette.filter(
    (defaultColor) =>
      !themePalette.some((themeColor) => themeColor.slug === defaultColor.slug),
  ),
];
