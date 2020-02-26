import React from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const FormStylingBackground = ({ children }) => {
  const { fontColor, backgroundColor } = useSelect(
    (select) => {
      const settings = select('mailpoet-form-editor').getFormSettings();
      return {
        backgroundColor: settings.backgroundColor,
        fontColor: settings.fontColor,
      };
    },
    []
  );
  return (
    <div style={{ backgroundColor, color: fontColor }}>
      {children}
    </div>
  );
};

FormStylingBackground.propTypes = {
  children: PropTypes.node.isRequired,
};

export default FormStylingBackground;
