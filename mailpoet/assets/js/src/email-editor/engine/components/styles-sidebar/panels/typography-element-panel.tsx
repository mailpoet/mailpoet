import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import {
  useSetting,
  // We can remove the ts-expect-error comments once the types are available.
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalFontAppearanceControl as FontAppearanceControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalLetterSpacingControl as LetterSpacingControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalFontFamilyControl as FontFamilyControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  LineHeightControl,
} from '@wordpress/block-editor';
import {
  FontSizePicker,
  __experimentalToolsPanel as ToolsPanel,
  __experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { useEmailStyles, StyleProperties } from '../../../hooks';

const DEFAULT_CONTROLS = {
  fontFamily: true,
  fontSize: true,
  fontAppearance: true,
  lineHeight: true,
  letterSpacing: true,
  textTransform: true,
  textDecoration: true,
  writingMode: true,
  textColumns: true,
};

export function TypographyElementPanel({
  element,
  headingLevel,
  defaultControls = DEFAULT_CONTROLS,
}: {
  element: string;
  headingLevel: string;
  defaultControls?: typeof DEFAULT_CONTROLS;
}) {
  const fontSizes = useSetting('typography.fontSizes');
  const { styles, defaultStyles, updateStyleProp } = useEmailStyles();

  let elementStyles = styles;
  let defaultElementStyles = defaultStyles;

  if (element !== 'text') {
    elementStyles = (styles.elements[element] || {}) as StyleProperties;
    defaultElementStyles = (defaultStyles.elements[element] ||
      {}) as StyleProperties;
  }

  if (element === 'heading' && styles.elements[headingLevel]) {
    elementStyles = {
      ...elementStyles,
      ...styles.elements[headingLevel],
    };
    defaultElementStyles = {
      ...defaultElementStyles,
      ...defaultStyles.elements[headingLevel],
    };
  }

  const {
    fontFamily,
    fontSize,
    fontStyle,
    fontWeight,
    lineHeight,
    letterSpacing,
  } = elementStyles.typography || {};
  const {
    fontFamily: defaultFontFamily,
    fontSize: defaultFontSize,
    fontStyle: defaultFontStyle,
    fontWeight: defaultFontWeight,
    lineHeight: defaultLineHeight,
    letterSpacing: defaultLetterSpacing,
  } = defaultElementStyles.typography || {};

  const updateElementStyleProp = useCallback(
    (path, newValue) => {
      if (element === 'heading') {
        updateStyleProp(['elements', headingLevel, ...path], newValue);
      } else if (element === 'text') {
        updateStyleProp([...path], newValue);
      } else {
        updateStyleProp(['elements', element, ...path], newValue);
      }
    },
    [element, updateStyleProp, headingLevel],
  );

  const setFontAppearance = ({
    fontStyle: newFontStyle,
    fontWeight: newFontWeight,
  }) => {
    const newTypography = {
      ...styles.typography,
      fontStyle: newFontStyle,
      fontWeight: newFontWeight,
    };
    updateElementStyleProp(['typography'], newTypography);
  };

  return (
    <ToolsPanel
      label={__('Typography', 'mailpoet')}
      resetAll={() => {
        updateElementStyleProp(['typography'], defaultElementStyles.typography);
      }}
    >
      <ToolsPanelItem
        label={__('Font family')}
        hasValue={() => fontFamily !== defaultFontFamily}
        onDeselect={() => {
          updateElementStyleProp(
            ['typography', 'fontFamily'],
            defaultFontFamily,
          );
        }}
        isShownByDefault={defaultControls.fontFamily}
      >
        <FontFamilyControl
          value={fontFamily}
          onChange={() => {}}
          size="__unstable-large"
          __nextHasNoMarginBottom
        />
      </ToolsPanelItem>
      {(element !== 'heading' || headingLevel !== 'heading') && (
        <ToolsPanelItem
          label={__('Font size')}
          hasValue={() => fontSize !== defaultFontSize}
          onDeselect={() => {
            updateElementStyleProp(['typography', 'fontSize'], defaultFontSize);
          }}
          isShownByDefault={defaultControls.fontSize}
        >
          <FontSizePicker
            value={fontSize}
            onChange={(newValue) => {
              updateElementStyleProp(['typography', 'fontSize'], newValue);
            }}
            fontSizes={fontSizes}
            disableCustomFontSizes={false}
            withReset={false}
            withSlider
            size="__unstable-large"
            __nextHasNoMarginBottom
          />
        </ToolsPanelItem>
      )}
      <ToolsPanelItem
        className="single-column"
        label={__('Appearance')}
        hasValue={() =>
          fontWeight !== defaultFontWeight || fontStyle !== defaultFontStyle
        }
        onDeselect={() => {
          setFontAppearance({
            fontStyle: defaultFontStyle,
            fontWeight: defaultFontWeight,
          });
        }}
        isShownByDefault={defaultControls.fontAppearance}
      >
        <FontAppearanceControl
          value={{
            fontStyle,
            fontWeight,
          }}
          onChange={setFontAppearance}
          hasFontStyles
          hasFontWeights
          size="__unstable-large"
          __nextHasNoMarginBottom
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        className="single-column"
        label={__('Line height')}
        hasValue={() => lineHeight !== defaultLineHeight}
        onDeselect={() => {
          updateElementStyleProp(
            ['typography', 'lineHeight'],
            defaultLineHeight,
          );
        }}
        isShownByDefault={defaultControls.lineHeight}
      >
        <LineHeightControl
          __nextHasNoMarginBottom
          __unstableInputWidth="auto"
          value={lineHeight}
          onChange={(newValue) => {
            updateElementStyleProp(['typography', 'lineHeight'], newValue);
          }}
          size="__unstable-large"
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        className="single-column"
        label={__('Letter spacing')}
        hasValue={() => letterSpacing !== defaultLetterSpacing}
        onDeselect={() => {
          updateElementStyleProp(
            ['typography', 'letterSpacing'],
            defaultLetterSpacing,
          );
        }}
        isShownByDefault={defaultControls.letterSpacing}
      >
        <LetterSpacingControl
          value={letterSpacing}
          onChange={(newValue) => {
            updateElementStyleProp(['typography', 'letterSpacing'], newValue);
          }}
          size="__unstable-large"
          __unstableInputWidth="auto"
        />
      </ToolsPanelItem>
    </ToolsPanel>
  );
}

export default TypographyElementPanel;
