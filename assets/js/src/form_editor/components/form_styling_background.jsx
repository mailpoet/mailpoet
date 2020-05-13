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
  return (
    <div
      className="mailpoet-form-background"
      style={{
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
        backgroundImage: `url(${backgroundImageUrl})`,
      }}
    >
      {children}
    </div>
  );
};

FormStylingBackground.propTypes = {
  children: PropTypes.node.isRequired,
};

export default FormStylingBackground;
