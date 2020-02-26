import React from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const FormStylingBackground = ({ children }) => {
  const { fontColor, backgroundColor, fontSize } = useSelect(
    (select) => {
      const settings = select('mailpoet-form-editor').getFormSettings();
      return {
        backgroundColor: settings.backgroundColor,
        fontColor: settings.fontColor,
        fontSize: settings.fontSize,
      };
    },
    []
  );

  let font;
  if (fontSize) font = Number(fontSize);
  return (
    <div
      style={{
        backgroundColor,
        color: fontColor,
        fontSize: font,
        lineHeight: 1.2,
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
