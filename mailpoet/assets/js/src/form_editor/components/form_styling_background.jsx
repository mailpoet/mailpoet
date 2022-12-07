import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

function FormStylingBackground({ children }) {
  const {
    fontColor,
    backgroundColor,
    gradient,
    fontSize,
    borderRadius,
    borderSize,
    borderColor,
    alignment,
    formPadding,
    backgroundImageUrl,
    backgroundImageDisplay,
    fontFamily,
  } = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );

  const previewSettings = useSelect(
    (select) => select('mailpoet-form-editor').getPreviewSettings(),
    [],
  );

  const formWidth = useSelect(
    (select) =>
      select('mailpoet-form-editor').getFormWidth(previewSettings.formType),
    [previewSettings.formType],
  );

  let borderStyle;
  if (borderSize && borderColor) {
    borderStyle = 'solid';
  }

  let fontNum = '';
  if (fontSize) fontNum = Number(fontSize);
  let radius;
  if (borderRadius) radius = Number(borderRadius);
  let padding;
  if (formPadding) padding = Number(formPadding);
  let textAlign;
  if (alignment) {
    textAlign = alignment;
  }
  const backgrounds = [];

  const style = {
    color: fontColor,
    fontSize: fontNum,
    fontFamily,
    lineHeight: 1.2,
    borderRadius: radius,
    borderWidth: borderSize,
    borderColor,
    borderStyle,
    textAlign,
    padding,
    width: formWidth.unit === 'pixel' ? formWidth.value : `${formWidth.value}%`,
    margin: '0 auto',
    maxWidth: '100%',
  };

  // Render virtual container for widgets and below pages/post forms with width in percent
  if (
    ['others', 'below_posts'].includes(previewSettings.formType) &&
    formWidth.unit === 'percent'
  ) {
    style.maxWidth = 600;
  }

  if (backgroundImageUrl !== undefined && backgroundImageUrl) {
    let backgroundPosition = 'center';
    let backgroundRepeat = 'no-repeat';
    let backgroundSize = 'cover';

    if (backgroundImageDisplay === 'fit') {
      backgroundSize = 'auto';
      backgroundPosition = 'center top';
    }
    if (backgroundImageDisplay === 'tile') {
      backgroundRepeat = 'repeat';
      backgroundSize = 'auto';
    }

    backgrounds.push(
      `url(${backgroundImageUrl}) ${backgroundPosition}/${backgroundSize} ${backgroundRepeat}`,
    );
  }

  if (gradient) {
    backgrounds.push(gradient);
  }

  if (backgroundColor) {
    backgrounds.push(backgroundColor);
  }

  if (backgrounds.length) {
    style.background = backgrounds.join(', ');
  }

  if (previewSettings.formType === 'fixed_bar') {
    const innerStyle = {
      width: style.width,
      margin: '0 auto',
    };
    style.width = 'max-content';
    style.minWidth = '100%';
    style.maxWidth = 'auto';

    return (
      <div className="mailpoet-form-background" style={style}>
        <div style={innerStyle}>{children}</div>
      </div>
    );
  }

  return (
    <div className="mailpoet-form-background" style={style}>
      {children}
    </div>
  );
}

FormStylingBackground.propTypes = {
  children: PropTypes.node.isRequired,
};
FormStylingBackground.displayName = 'FormStylingBackground';
export { FormStylingBackground };
