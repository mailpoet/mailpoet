import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const MethodMailChimp = ({ setInputValid }) => {
  return (
    <div>
      MethodMailChimp
    </div>
  );
};

MethodMailChimp.propTypes = {
  setInputValid: PropTypes.func,
};

MethodMailChimp.defaultProps = {
  setInputValid: () => {},
};

export default MethodMailChimp;
