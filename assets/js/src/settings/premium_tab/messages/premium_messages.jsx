import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const validMessage = (
  <div className="mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumValidMessage')}
  </div>
);

const notValidMessage = (message) => (
  <div className="mailpoet_error">
    {message || MailPoet.I18n.t('premiumTabPremiumNotValidMessage')}
  </div>
);

const PremiumMessages = (props) => {
  if (props.keyValid) {
    return validMessage;
  }
  return notValidMessage(props.keyMessage);
};

PremiumMessages.propTypes = {
  keyValid: PropTypes.bool.isRequired,
  keyMessage: PropTypes.string,
};

PremiumMessages.defaultProps = {
  keyMessage: null,
};

export default PremiumMessages;
