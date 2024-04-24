import deepmerge from 'deepmerge';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { storeName, TypographyProperties } from '../store';
import { useEmailTheme } from './use-email-theme';

interface ElementProperties {
  typography: TypographyProperties;
}
export interface StyleProperties {
  spacing?: {
    padding: {
      top: string;
      right: string;
      bottom: string;
      left: string;
    };
    blockGap: string;
  };
  typography?: TypographyProperties;
  color?: {
    background: string;
    text: string;
  };
  elements?: Record<string, ElementProperties>;
}

interface EmailStylesData {
  styles: StyleProperties;
  defaultStyles: StyleProperties;
  updateStyleProp: (path, newValue) => void;
}

/**
 * Immutably sets a value inside an object. Like `lodash#set`, but returning a
 * new object. Treats nullish initial values as empty objects. Clones any
 * nested objects. Supports arrays, too.
 *
 * @param {Object}              object Object to set a value in.
 * @param {number|string|Array} setPath   Path in the object to modify.
 * @param {*}                   value  New value to set.
 * @return {Object} Cloned object with the new value set.
 */
export function setImmutably(setObject, setPath, value): typeof setObject {
  // Normalize path
  const path = Array.isArray(setPath) ? [...setPath] : [setPath];

  // Shallowly clone the base of the object
  const object = Array.isArray(setObject) ? [...setObject] : { ...setObject };

  const leaf = path.pop();

  // Traverse object from root to leaf, shallowly cloning at each level
  let prev = object;

  path.forEach((key) => {
    const lvl = prev[key];
    prev[key] = Array.isArray(lvl) ? [...lvl] : { ...lvl };
    prev = prev[key];
  });

  prev[leaf] = value;

  return object;
}

export const useEmailStyles = (): EmailStylesData => {
  const { templateTheme, updateTemplateTheme } = useEmailTheme();

  // This is email level styling stored in post meta.
  const styles = templateTheme?.styles;

  // Default styles from theme.json.
  const { styles: defaultStyles } = useSelect((select) => ({
    styles: select(storeName).getStyles(),
  }));

  // Update email styles.
  const updateStyleProp = useCallback(
    (path, newValue) => {
      const newTheme = setImmutably(
        templateTheme,
        ['styles', ...path],
        newValue,
      );
      updateTemplateTheme(newTheme);
    },
    [updateTemplateTheme, templateTheme],
  );

  return {
    styles: {
      spacing: {
        ...defaultStyles.spacing,
        ...styles?.spacing,
      },
      typography: {
        ...defaultStyles.typography,
        ...styles?.typography,
      },
      elements: deepmerge.all([
        defaultStyles.elements || {},
        styles?.elements || {},
      ]) as Record<string, ElementProperties>,
    },
    defaultStyles,
    updateStyleProp,
  };
};
