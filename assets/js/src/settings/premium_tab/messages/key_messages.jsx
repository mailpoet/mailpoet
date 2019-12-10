import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const keyValidMessage = (
  <div className="mailpoet_success_item mailpoet_success">
    {MailPoet.I18n.t('premiumTabKeyValidMessage')}
  </div>
);

const keyNotValidMessage = (
  <div className="mailpoet_error_item mailpoet_error">
    {MailPoet.I18n.t('premiumTabKeyNotValidMessage')}
  </div>
);

const KeyMessages = (props) => (props.keyValid ? keyValidMessage : keyNotValidMessage);

KeyMessages.propTypes = {
  keyValid: PropTypes.bool.isRequired,
};

export default KeyMessages;
