export function getEditorExperimentalFeatures(
  colors: unknown,
  gradients: unknown,
  fontSizes: unknown,
) {
  return {
    spacing: {
      units: ['px', 'em', 'rem', 'vh', 'vw', '%'],
    },
    useRootPaddingAwareAlignments: true,
    color: {
      custom: true,
      text: true,
      background: true,
      customGradient: true,
      defaultPalette: true,
      palette: {
        default: colors,
      },
      gradients: {
        default: gradients,
      },
    },
    typography: {
      defaultFontSizes: true,
      textAlign: true,
      fontSizes: {
        default: fontSizes,
      },
      // Disable built-in font family toolbar control - MailPoet uses its own.
      fontFamilies: {
        default: [],
      },
    },
  };
}
