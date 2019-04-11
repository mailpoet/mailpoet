import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const MethodMailChimp = ({ setInputValid, setInputInvalid, onValueChange }) => {
  const [key, setKey] = useState('');

  return (
    <>
      <label htmlFor="paste_input" className="import_method_paste">
        <div>
          <span className="import_heading">{MailPoet.I18n.t('methodMailChimpLabel')}</span>
        </div>
        <input
          id="paste_input"
          type="text"
          onChange={setKey}
        />
        <button className="button" type="button">
          {MailPoet.I18n.t('methodMailChimpVerify')}
        </button>
        <span className="mailpoet_mailchimp-key-status"></span>
      </label>
    </>
  );
};

MethodMailChimp.propTypes = {
  setInputValid: PropTypes.func,
  setInputInvalid: PropTypes.func,
  onValueChange: PropTypes.func.isRequired,
};

MethodMailChimp.defaultProps = {
  setInputValid: () => {},
  setInputInvalid: () => {},
};

export default MethodMailChimp;
