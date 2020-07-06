import React from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const FormStylingBackground = ({ children }) => {
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
  } = useSelect((select) => select('mailpoet-form-editor').getFormSettings(), []);

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
    width: 700,
    margin: '0 auto',
  };

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

    backgrounds.push(`url(${backgroundImageUrl}) ${backgroundPosition}/${backgroundSize} ${backgroundRepeat}`);
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

  return (
    <div className="mailpoet-form-background" style={style}>
      {children}
    </div>
  );
};

FormStylingBackground.propTypes = {
  children: PropTypes.node.isRequired,
};

export default FormStylingBackground;
