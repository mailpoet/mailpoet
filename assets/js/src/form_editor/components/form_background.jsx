import React from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const FormBackground = ({ children }) => {
  const backgroundColor = useSelect(
    (select) => {
      const settings = select('mailpoet-form-editor').getFormSettings();
      return settings.backgroundColor;
    },
    []
  );
  return (
    <div style={{ backgroundColor }}>
      {children}
    </div>
  );
};

FormBackground.propTypes = {
  children: PropTypes.node.isRequired,
};

export default FormBackground;
