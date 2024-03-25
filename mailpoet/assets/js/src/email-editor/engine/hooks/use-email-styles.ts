import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { EmailTheme, storeName } from '../store';

interface StyleProperties {
  spacing: {
    padding: {
      top: string;
      right: string;
      bottom: string;
      left: string;
    };
    blockGap: string;
  };
  typography: {
    fontFamily: string;
    fontStyle: string;
    fontWeight: string;
    letterSpacing: string;
  };
}
interface EmailStylesData {
  styles: StyleProperties;
  defaultStyles: StyleProperties;
  updateSpacingProp: (
    property: keyof StyleProperties['spacing'],
    value,
  ) => void;
  resetSpacingProp: (property?: keyof StyleProperties['spacing']) => void;
}

export const useEmailStyles = (): EmailStylesData => {
  const [meta, setMeta] = useEntityProp('postType', 'mailpoet_email', 'meta');

  // This is email level styling stored in post meta.
  const emailTheme = meta?.mailpoet_email_theme as EmailTheme;
  const styles = emailTheme?.styles;

  // Default styles from theme.json.
  const { styles: defaultStyles } = useSelect((select) => ({
    styles: select(storeName).getStyles(),
  }));

  // Update email styles.
  const updateEmailTheme = useCallback(
    (newValue) => {
      setMeta({ ...meta, mailpoet_email_theme: newValue });
    },
    [setMeta, meta],
  );

  const updateSpacingProp = useCallback(
    (property: keyof StyleProperties['spacing'], value) => {
      updateEmailTheme({
        ...emailTheme,
        styles: {
          ...emailTheme?.styles,
          spacing: {
            ...emailTheme?.styles?.spacing,
            [property]: value,
          },
        },
      });
    },
    [updateEmailTheme, emailTheme],
  );

  const resetSpacingProp = useCallback(
    (property?: keyof StyleProperties['spacing']) => {
      if (!property) {
        updateEmailTheme({
          ...emailTheme,
          styles: {
            ...emailTheme?.styles,
            spacing: defaultStyles.spacing,
          },
        });
        return;
      }
      updateEmailTheme({
        ...emailTheme,
        styles: {
          ...emailTheme?.styles,
          spacing: {
            ...emailTheme?.styles?.spacing,
            [property]: defaultStyles.spacing[property],
          },
        },
      });
    },
    [updateEmailTheme, emailTheme, defaultStyles],
  );

  return {
    styles: {
      spacing: {
        padding: styles?.spacing?.padding ?? defaultStyles.spacing.padding,
        blockGap: styles?.spacing?.blockGap ?? defaultStyles.spacing.blockGap,
      },
      typography: {
        fontFamily:
          styles?.typography?.fontFamily ?? defaultStyles.typography.fontFamily,
        fontStyle:
          styles?.typography?.fontStyle ?? defaultStyles.typography.fontStyle,
        fontWeight:
          styles?.typography?.fontWeight ?? defaultStyles.typography.fontWeight,
        letterSpacing:
          styles?.typography?.letterSpacing ??
          defaultStyles.typography.letterSpacing,
      },
    },
    defaultStyles: {
      spacing: {
        padding: defaultStyles.spacing.padding,
        blockGap: defaultStyles.spacing.blockGap,
      },
      typography: {
        fontFamily: defaultStyles.typography.fontFamily,
        fontStyle: defaultStyles.typography.fontStyle,
        fontWeight: defaultStyles.typography.fontWeight,
        letterSpacing: defaultStyles.typography.letterSpacing,
      },
    },
    updateSpacingProp,
    resetSpacingProp,
  };
};
