import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const validMessage = (
  <div className="mailpoet_success">
    {MailPoet.I18n.t('premiumTabMssValidMessage')}
  </div>
);

const notValidMessage = (message) => (
  <div className="mailpoet_error">
    {message || MailPoet.I18n.t('premiumTabMssNotValidMessage')}
  </div>
);

const MssMessages = (props) => {
  if (props.keyValid) {
    return validMessage;
  }
  return notValidMessage(props.keyMessage);
};

MssMessages.propTypes = {
  keyValid: PropTypes.bool.isRequired,
  keyMessage: PropTypes.string,
};

MssMessages.defaultProps = {
  keyMessage: null,
};

export default MssMessages;
