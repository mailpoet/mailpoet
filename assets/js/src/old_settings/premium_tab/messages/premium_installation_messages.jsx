import PropTypes from 'prop-types';
import React from 'react';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';

const PremiumInstallationStatus = {
  INSTALL_INSTALLING: 0,
  INSTALL_ACTIVATING: 1,
  INSTALL_DONE: 2,
  INSTALL_INSTALLING_ERROR: 3,
  INSTALL_ACTIVATING_ERROR: 4,
  ACTIVATE_ACTIVATING: 5,
  ACTIVATE_DONE: 6,
  ACTIVATE_ERROR: 7,
};

const keyPrefix = 'premium-installation-message';

const installingMessage = () => (
  <div className="mailpoet_subitem" key={`${keyPrefix}-installing`}>
    {MailPoet.I18n.t('premiumTabPremiumInstallationInstallingMessage')}
  </div>
);

const activatingMessage = () => (
  <div className="mailpoet_subitem" key={`${keyPrefix}-activating`}>
    {MailPoet.I18n.t('premiumTabPremiumInstallationActivatingMessage')}
  </div>
);

const doneMessage = () => (
  <div className="mailpoet_subitem" key={`${keyPrefix}-done`}>
    <strong>{MailPoet.I18n.t('premiumTabPremiumInstallationActiveMessage')}</strong>
  </div>
);

const errorMessage = () => {
  const links = ['https://account.mailpoet.com', 'https://www.mailpoet.com/support/'];
  const message = ReactStringReplace(
    MailPoet.I18n.t('premiumTabPremiumInstallationErrorMessage'),
    /\[link\](.*?)\[\/link\]/g,
    (match, i) => (
      <a
        key={i}
        href={links.shift()}
        target="_blank"
        rel="noopener noreferrer"
      >
        {match}
      </a>
    )
  );

  return (
    <div className="mailpoet_subitem" key={`${keyPrefix}-error`}>
      <strong>{message}</strong>
    </div>
  );
};

const PremiumInstallationMessages = (props) => {
  switch (props.installationStatus) {
    case PremiumInstallationStatus.INSTALL_INSTALLING:
      return installingMessage();
    case PremiumInstallationStatus.INSTALL_ACTIVATING:
      return [installingMessage(), activatingMessage()];
    case PremiumInstallationStatus.INSTALL_DONE:
      return [installingMessage(), activatingMessage(), doneMessage()];
    case PremiumInstallationStatus.INSTALL_INSTALLING_ERROR:
      return [installingMessage(), errorMessage()];
    case PremiumInstallationStatus.INSTALL_ACTIVATING_ERROR:
      return [installingMessage(), activatingMessage(), errorMessage()];
    case PremiumInstallationStatus.ACTIVATE_ACTIVATING:
      return activatingMessage();
    case PremiumInstallationStatus.ACTIVATE_DONE:
      return [activatingMessage(), doneMessage()];
    case PremiumInstallationStatus.ACTIVATE_ERROR:
      return [activatingMessage(), errorMessage()];
    default:
      return null;
  }
};

PremiumInstallationMessages.propTypes = {
  installationStatus: PropTypes.number,
};

export { PremiumInstallationStatus, PremiumInstallationMessages };
