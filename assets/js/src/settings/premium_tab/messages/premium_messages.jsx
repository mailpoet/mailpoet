import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import { PremiumInstallationMessages } from 'settings/premium_tab/messages/premium_installation_messages.jsx';

const PremiumStatus = {
  KEY_INVALID: 0,
  KEY_VALID_PREMIUM_PLUGIN_NOT_INSTALLED: 1,
  KEY_VALID_PREMIUM_PLUGIN_NOT_ACTIVE: 2,
  KEY_VALID_PREMIUM_PLUGIN_ACTIVE: 3,
  KEY_VALID_PREMIUM_PLUGIN_BEING_INSTALLED: 4,
  KEY_VALID_PREMIUM_PLUGIN_BEING_ACTIVATED: 5,
};

const activeMessage = (
  <div className="mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumActiveMessage')}
  </div>
);

const installingMessage = (
  <div className="mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumInstallingMessage')}
  </div>
);

const activatingMessage = (
  <div className="mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumActivatingMessage')}
  </div>
);

const premiumNotInstalledMessage = (installationCallback) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabPremiumNotInstalledMessage')}
    {' '}
    <button type="button" className="button-link" onClick={installationCallback}>
      {MailPoet.I18n.t('premiumTabPremiumInstallMessage')}
    </button>
  </div>
);

const premiumNotActiveMessage = (activationCallback) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabPremiumNotActiveMessage')}
    {' '}
    <button type="button" className="button-link" onClick={activationCallback}>
      {MailPoet.I18n.t('premiumTabPremiumActivateMessage')}
    </button>
  </div>
);

const notValidMessage = (message) => (
  <div className="mailpoet_error">
    {message}
  </div>
);

const getMessageFromStatus = (status, message, installationCallback, activationCallback) => {
  switch (status) {
    case PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_ACTIVE:
      return activeMessage;
    case PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_NOT_ACTIVE:
      return premiumNotActiveMessage(activationCallback);
    case PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_NOT_INSTALLED:
      return premiumNotInstalledMessage(installationCallback);
    case PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_INSTALLED:
      return installingMessage;
    case PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_ACTIVATED:
      return activatingMessage;
    case PremiumStatus.KEY_INVALID:
      return message ? notValidMessage(message) : null;
    default:
      return null;
  }
};

const PremiumMessages = (props) => {
  const message = getMessageFromStatus(
    props.keyStatus,
    props.keyMessage,
    props.installationCallback,
    props.activationCallback
  );

  if (!message) {
    return null;
  }

  return (
    <>
      {message}
      <PremiumInstallationMessages installationStatus={props.installationStatus} />
    </>
  );
};

PremiumMessages.propTypes = {
  keyStatus: PropTypes.number.isRequired,
  keyMessage: PropTypes.string,
  installationStatus: PropTypes.number,
  installationCallback: PropTypes.func.isRequired,
  activationCallback: PropTypes.func.isRequired,
};

PremiumMessages.defaultProps = {
  keyMessage: null,
  installationStatus: null,
};

export { PremiumStatus, PremiumMessages };
