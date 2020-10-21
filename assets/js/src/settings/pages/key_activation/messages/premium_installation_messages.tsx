import React from 'react';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';
import { PremiumInstallationStatus } from 'settings/store/types';

const keyPrefix = 'premium-installation-message';

const InstallingMessage = () => (
  <div className="mailpoet_subitem" key={`${keyPrefix}-installing`}>
    {MailPoet.I18n.t('premiumTabPremiumInstallationInstallingMessage')}
  </div>
);

const ActivatingMessage = () => (
  <div className="mailpoet_subitem" key={`${keyPrefix}-activating`}>
    {MailPoet.I18n.t('premiumTabPremiumInstallationActivatingMessage')}
  </div>
);

const DoneMessage = () => (
  <div className="mailpoet_subitem" key={`${keyPrefix}-done`}>
    <strong>{MailPoet.I18n.t('premiumTabPremiumInstallationActiveMessage')}</strong>
  </div>
);

const ErrorMessage = () => {
  const links = ['https://account.mailpoet.com', 'https://www.mailpoet.com/support/'];
  const message = ReactStringReplace(
    MailPoet.I18n.t('premiumTabPremiumInstallationErrorMessage'),
    /\[link\](.*?)\[\/link\]/g,
    (match, i) => (
      <a
        className="mailpoet-link"
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

type Props = {
  installationStatus: PremiumInstallationStatus
}
export default function PremiumInstallationMessages(props: Props) {
  switch (props.installationStatus) {
    case PremiumInstallationStatus.INSTALL_INSTALLING:
      return <InstallingMessage />;
    case PremiumInstallationStatus.INSTALL_ACTIVATING:
      return (
        <>
          <InstallingMessage />
          <ActivatingMessage />
        </>
      );
    case PremiumInstallationStatus.INSTALL_DONE:
      return (
        <>
          <InstallingMessage />
          <ActivatingMessage />
          <DoneMessage />
        </>
      );
    case PremiumInstallationStatus.INSTALL_INSTALLING_ERROR:
      return (
        <>
          <InstallingMessage />
          <ErrorMessage />
        </>
      );
    case PremiumInstallationStatus.INSTALL_ACTIVATING_ERROR:
      return (
        <>
          <InstallingMessage />
          <ActivatingMessage />
          <ErrorMessage />
        </>
      );
    case PremiumInstallationStatus.ACTIVATE_ACTIVATING:
      return <ActivatingMessage />;
    case PremiumInstallationStatus.ACTIVATE_DONE:
      return (
        <>
          <ActivatingMessage />
          <DoneMessage />
        </>
      );
    case PremiumInstallationStatus.ACTIVATE_ERROR:
      return (
        <>
          <ActivatingMessage />
          <ErrorMessage />
        </>
      );
    default:
      return null;
  }
}
