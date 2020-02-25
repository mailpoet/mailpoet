import React from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const FormTextColor = ({ children }) => {
  const fontColor = useSelect(
    (select) => {
      const settings = select('mailpoet-form-editor').getFormSettings();
      return settings.fontColor;
    },
    []
  );
  return (
    <div style={{ color: fontColor }}>
      {children}
    </div>
  );
};

FormTextColor.propTypes = {
  children: PropTypes.node.isRequired,
};

export default FormTextColor;
