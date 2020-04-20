import React from 'react';
import MailPoet from 'mailpoet';
import { useSelector } from 'settings/store/hooks/index';
import { PremiumInstallationStatus, PremiumStatus } from 'settings/store/types';
import PremiumInstallationMessages from './premium_installation_messages';

const ActiveMessage = () => (
  <div className="mailpoet_success_item mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumActiveMessage')}
  </div>
);

const InstallingMessage = () => (
  <div className="mailpoet_success_item mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumInstallingMessage')}
  </div>
);

const ActivatingMessage = () => (
  <div className="mailpoet_success_item mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumActivatingMessage')}
  </div>
);

type PremiumNotInstalledMessageProps = { callback: () => any }
const PremiumNotInstalledMessage = ({ callback }: PremiumNotInstalledMessageProps) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabPremiumNotInstalledMessage')}
    {' '}
    <button type="button" className="button-link" onClick={callback}>
      {MailPoet.I18n.t('premiumTabPremiumInstallMessage')}
    </button>
  </div>
);

type PremiumNotActiveMessageProps = { callback: () => any }
const PremiumNotActiveMessage = ({ callback }: PremiumNotActiveMessageProps) => (
  <div className="mailpoet_error">
    {MailPoet.I18n.t('premiumTabPremiumNotActiveMessage')}
    {' '}
    <button type="button" className="button-link" onClick={callback}>
      {MailPoet.I18n.t('premiumTabPremiumActivateMessage')}
    </button>
  </div>
);

type NotValidMessageProps = { message?: string }
const NotValidMessage = ({ message }: NotValidMessageProps) => (
  <div className="mailpoet_error">
    {message || MailPoet.I18n.t('premiumTabPremiumKeyNotValidMessage')}
  </div>
);
NotValidMessage.defaultProps = {
  keyMessage: '',
};

type Props = {
  keyMessage?: string
  activationCallback: () => any
  installationCallback: () => any
  installationStatus: PremiumInstallationStatus
}
export default function PremiumMessages(props: Props) {
  const { premiumStatus: status } = useSelector('getKeyActivationState')();

  // when activity sub-messages shown, keep the top-level installing/activating messages
  let displayStatus = status;
  const premiumInstallationStatusName = PremiumInstallationStatus[props.installationStatus] ?? '';
  if (premiumInstallationStatusName.startsWith('INSTALL_')) {
    displayStatus = PremiumStatus.VALID_PREMIUM_PLUGIN_BEING_INSTALLED;
  } else if (premiumInstallationStatusName.startsWith('ACTIVATE_')) {
    displayStatus = PremiumStatus.VALID_PREMIUM_PLUGIN_BEING_ACTIVATED;
  }

  switch (displayStatus) {
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_INSTALLED:
      return (
        <>
          <PremiumNotInstalledMessage callback={props.installationCallback} />
          <PremiumInstallationMessages installationStatus={props.installationStatus} />
        </>
      );
    case PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE:
      return (
        <>
          <ActiveMessage />
          <PremiumInstallationMessages installationStatus={props.installationStatus} />
        </>
      );
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE:
      return (
        <>
          <PremiumNotActiveMessage callback={props.activationCallback} />
          <PremiumInstallationMessages installationStatus={props.installationStatus} />
        </>
      );
    case PremiumStatus.VALID_PREMIUM_PLUGIN_BEING_INSTALLED:
      return (
        <>
          <InstallingMessage />
          <PremiumInstallationMessages installationStatus={props.installationStatus} />
        </>
      );
    case PremiumStatus.VALID_PREMIUM_PLUGIN_BEING_ACTIVATED:
      return (
        <>
          <ActivatingMessage />
          <PremiumInstallationMessages installationStatus={props.installationStatus} />
        </>
      );
    case PremiumStatus.INVALID:
      return (
        <>
          <NotValidMessage message={props.keyMessage} />
          <PremiumInstallationMessages installationStatus={props.installationStatus} />
        </>
      );
    default:
      return null;
  }
}
PremiumMessages.defaultProps = {
  keyMessage: '',
};
