import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const MethodUpload = ({ setInputValid }) => {
  return (
    <div>
      MethodUpload
    </div>
  );
};

MethodUpload.propTypes = {
  setInputValid: PropTypes.func,
};

MethodUpload.defaultProps = {
  setInputValid: () => {},
};

export default MethodUpload;
