import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const MssStatus = {
  KEY_INVALID: 0,
  KEY_VALID_MSS_NOT_ACTIVE: 1,
  KEY_VALID_MSS_ACTIVE: 2,
};

const activeMessage = () => (
  <div className="mailpoet_success mailpoet_mss_key_valid">
    {MailPoet.I18n.t('premiumTabMssActiveMessage')}
  </div>
);

const notValidMessage = (message) => (
  <div className="mailpoet_error">
    {message || MailPoet.I18n.t('premiumTabMssKeyNotValidMessage')}
  </div>
);

const mssNotActiveMessage = (activationCallback) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabMssNotActiveMessage')}
    {' '}
    <button type="button" className="button-link" onClick={activationCallback}>
      {MailPoet.I18n.t('premiumTabMssActivateMessage')}
    </button>
  </div>
);


const MssMessages = (props) => {
  switch (props.keyStatus) {
    case MssStatus.KEY_VALID_MSS_ACTIVE:
      return activeMessage();
    case MssStatus.KEY_VALID_MSS_NOT_ACTIVE:
      return mssNotActiveMessage(props.activationCallback);
    case MssStatus.KEY_INVALID:
      return notValidMessage(props.keyMessage);
    default:
      return null;
  }
};

MssMessages.propTypes = {
  keyStatus: PropTypes.number.isRequired,
  keyMessage: PropTypes.string,
  activationCallback: PropTypes.func.isRequired,
};

MssMessages.defaultProps = {
  keyMessage: null,
};

export { MssStatus, MssMessages };
