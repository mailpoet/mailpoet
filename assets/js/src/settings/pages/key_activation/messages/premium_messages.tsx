import React from 'react';
import MailPoet from 'mailpoet';
import { useSelector } from 'settings/store/hooks/index';
import { PremiumStatus } from 'settings/store/types';
import Button from '../../../../common/button/button';

const ActiveMessage = () => (
  <div className="mailpoet_success_item mailpoet_success">
    {MailPoet.I18n.t('premiumTabPremiumActiveMessage')}
  </div>
);

type PremiumNotActiveMessageProps = {
  url?: string;
}
const PremiumNotActiveMessage = (props: PremiumNotActiveMessageProps) => (
  <>
    <div className="mailpoet_error mailpoet_install_premium_message">
      {MailPoet.I18n.t('premiumTabPremiumNotActiveMessage')}
    </div>
    {props.url && (
      <Button href={props.url}>
        {MailPoet.I18n.t('premiumTabPremiumActivateMessage')}
      </Button>
    )}
  </>
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
  keyMessage?: string;
}
export default function PremiumMessages(props: Props) {
  const { premiumStatus: status, downloadUrl } = useSelector('getKeyActivationState')();

  switch (status) {
    case PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE:
      return (
        <>
          <ActiveMessage />
        </>
      );
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE:
      return (
        <>
          <PremiumNotActiveMessage url={downloadUrl} />
        </>
      );
    case PremiumStatus.INVALID:
      return (
        <>
          <NotValidMessage message={props.keyMessage} />
        </>
      );
    default:
      return null;
  }
}
PremiumMessages.defaultProps = {
  keyMessage: '',
};
