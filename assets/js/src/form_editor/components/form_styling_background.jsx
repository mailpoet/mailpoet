import React from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const FormStylingBackground = ({ children }) => {
  const {
    fontColor,
    backgroundColor,
    fontSize,
    borderRadius,
    borderSize,
    borderColor,
    alignment,
    formPadding,
    backgroundImageUrl,
    backgroundImageDisplay,
  } = useSelect((select) => select('mailpoet-form-editor').getFormSettings(), []);

  let borderStyle;
  if (borderSize && borderColor) {
    borderStyle = 'solid';
  }

  let font;
  if (fontSize) font = Number(fontSize);
  let radius;
  if (borderRadius) radius = Number(borderRadius);
  let padding;
  if (formPadding) padding = Number(formPadding);
  let textAlign;
  if (alignment) {
    textAlign = alignment;
  }

  let style = {
    backgroundColor,
    color: fontColor,
    fontSize: font,
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

  if (backgroundImageUrl !== undefined) {
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
    style = {
      ...style,
      backgroundRepeat,
      backgroundPosition,
      backgroundSize,
      backgroundImage: `url(${backgroundImageUrl})`,
    };
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
