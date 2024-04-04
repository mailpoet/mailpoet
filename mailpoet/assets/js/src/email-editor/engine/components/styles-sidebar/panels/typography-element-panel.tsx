import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import {
  useSetting,
  // We can remove the ts-expect-error comments once the types are available.
  // @see packages/block-editor/src/components/index.js
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalFontAppearanceControl as FontAppearanceControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalLetterSpacingControl as LetterSpacingControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalFontFamilyControl as FontFamilyControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  LineHeightControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalTextDecorationControl as TextDecorationControl,
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalTextTransformControl as TextTransformControl,
} from '@wordpress/block-editor';
import {
  FontSizePicker,
  __experimentalToolsPanel as ToolsPanel,
  __experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { useEmailStyles } from '../../../hooks';
import { getElementStyles } from '../utils';

export const DEFAULT_CONTROLS = {
  fontFamily: true,
  fontSize: true,
  fontAppearance: true,
  lineHeight: true,
  letterSpacing: false,
  textTransform: false,
  textDecoration: false,
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
  const elementStyles = getElementStyles(styles, element, headingLevel);
  const defaultElementStyles = getElementStyles(
    defaultStyles,
    element,
    headingLevel,
  );

  const {
    fontFamily,
    fontSize,
    fontStyle,
    fontWeight,
    lineHeight,
    letterSpacing,
    textDecoration,
    textTransform,
  } = elementStyles.typography;

  const {
    fontFamily: defaultFontFamily,
    fontSize: defaultFontSize,
    fontStyle: defaultFontStyle,
    fontWeight: defaultFontWeight,
    lineHeight: defaultLineHeight,
    letterSpacing: defaultLetterSpacing,
    textDecoration: defaultTextDecoration,
    textTransform: defaultTextTransform,
  } = defaultElementStyles.typography;

  const hasFontFamily = () => fontFamily !== defaultFontFamily;
  const hasFontSize = () => fontSize !== defaultFontSize;
  const hasFontAppearance = () =>
    fontWeight !== defaultFontWeight || fontStyle !== defaultFontStyle;
  const hasLineHeight = () => lineHeight !== defaultLineHeight;
  const hasLetterSpacing = () => letterSpacing !== defaultLetterSpacing;
  const hasTextDecoration = () => textDecoration !== defaultTextDecoration;
  const hasTextTransform = () => textTransform !== defaultTextTransform;
  const showToolFontSize = element !== 'heading' || headingLevel !== 'heading';

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

  const setLetterSpacing = (newValue) => {
    updateElementStyleProp(['typography', 'letterSpacing'], newValue);
  };

  const setLineHeight = (newValue) => {
    updateElementStyleProp(['typography', 'lineHeight'], newValue);
  };

  const setFontSize = (newValue) => {
    updateElementStyleProp(['typography', 'fontSize'], newValue);
  };

  const setFontFamily = (newValue) => {
    updateElementStyleProp(['typography', 'fontFamily'], newValue);
  };

  const setTextDecoration = (newValue) => {
    updateElementStyleProp(['typography', 'textDecoration'], newValue);
  };

  const setTextTransform = (newValue) => {
    updateElementStyleProp(['typography', 'textTransform'], newValue);
  };

  const setFontAppearance = ({
    fontStyle: newFontStyle,
    fontWeight: newFontWeight,
  }) => {
    updateElementStyleProp(['typography', 'fontStyle'], newFontStyle);
    updateElementStyleProp(['typography', 'fontWeight'], newFontWeight);
  };

  const resetAll = () => {
    updateElementStyleProp(['typography'], defaultElementStyles.typography);
  };

  return (
    <ToolsPanel label={__('Typography', 'mailpoet')} resetAll={resetAll}>
      <ToolsPanelItem
        label={__('Font family')}
        hasValue={hasFontFamily}
        onDeselect={() => setFontFamily(defaultFontFamily)}
        isShownByDefault={defaultControls.fontFamily}
      >
        <FontFamilyControl
          value={fontFamily}
          onChange={setFontFamily}
          size="__unstable-large"
          __nextHasNoMarginBottom
        />
      </ToolsPanelItem>
      {showToolFontSize && (
        <ToolsPanelItem
          label={__('Font size')}
          hasValue={hasFontSize}
          onDeselect={() => setFontSize(defaultFontSize)}
          isShownByDefault={defaultControls.fontSize}
        >
          <FontSizePicker
            value={fontSize}
            onChange={setFontSize}
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
        hasValue={hasFontAppearance}
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
        hasValue={hasLineHeight}
        onDeselect={() => setLineHeight(defaultLineHeight)}
        isShownByDefault={defaultControls.lineHeight}
      >
        <LineHeightControl
          __nextHasNoMarginBottom
          __unstableInputWidth="auto"
          value={lineHeight}
          onChange={setLineHeight}
          size="__unstable-large"
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        className="single-column"
        label={__('Letter spacing')}
        hasValue={hasLetterSpacing}
        onDeselect={() => setLetterSpacing(defaultLetterSpacing)}
        isShownByDefault={defaultControls.letterSpacing}
      >
        <LetterSpacingControl
          value={letterSpacing}
          onChange={setLetterSpacing}
          size="__unstable-large"
          __unstableInputWidth="auto"
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        className="single-column"
        label={__('Text decoration')}
        hasValue={hasTextDecoration}
        onDeselect={() => setTextDecoration(defaultTextDecoration)}
        isShownByDefault={defaultControls.textDecoration}
      >
        <TextDecorationControl
          value={textDecoration}
          onChange={setTextDecoration}
          size="__unstable-large"
          __unstableInputWidth="auto"
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        label={__('Letter case')}
        hasValue={hasTextTransform}
        onDeselect={() => setTextTransform(defaultTextTransform)}
        isShownByDefault={defaultControls.textTransform}
      >
        <TextTransformControl
          value={textTransform}
          onChange={setTextTransform}
          showNone
          isBlock
          size="__unstable-large"
          __nextHasNoMarginBottom
        />
      </ToolsPanelItem>
    </ToolsPanel>
  );
}

export default TypographyElementPanel;
