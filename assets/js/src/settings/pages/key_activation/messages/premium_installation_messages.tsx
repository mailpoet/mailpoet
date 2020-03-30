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
    case 'install_installing':
      return <InstallingMessage />;
    case 'install_activating':
      return (
        <>
          <InstallingMessage />
          <ActivatingMessage />
        </>
      );
    case 'install_done':
      return (
        <>
          <InstallingMessage />
          <ActivatingMessage />
          <DoneMessage />
        </>
      );
    case 'install_installing_error':
      return (
        <>
          <InstallingMessage />
          <ErrorMessage />
        </>
      );
    case 'install_activating_error':
      return (
        <>
          <InstallingMessage />
          <ActivatingMessage />
          <ErrorMessage />
        </>
      );
    case 'activate_activating':
      return <ActivatingMessage />;
    case 'activate_done':
      return (
        <>
          <ActivatingMessage />
          <DoneMessage />
        </>
      );
    case 'activate_error':
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
