import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { useSelector } from 'settings/store/hooks';
import { PremiumStatus } from 'settings/store/types';
import { Button } from 'common/button/button';

type ActiveMessageProps = { canUseSuccessClass: boolean };
function ActiveMessage(props: ActiveMessageProps) {
  return (
    <div
      className={classnames('mailpoet_success_item', {
        mailpoet_success: props.canUseSuccessClass,
      })}
    >
      {MailPoet.I18n.t('premiumTabPremiumActiveMessage')}
    </div>
  );
}

type PremiumNotActiveMessageProps = {
  url?: string;
};

function PremiumNotActiveMessage(props: PremiumNotActiveMessageProps) {
  return (
    <>
      <div className="mailpoet_error mailpoet_install_premium_message">
        {MailPoet.I18n.t('premiumTabPremiumNotActivatedMessage')}
      </div>
      {props.url && (
        <Button href={props.url}>
          {MailPoet.I18n.t('premiumTabPremiumActivateMessage')}
        </Button>
      )}
    </>
  );
}

function PremiumNotInstalledMessage(props: PremiumNotActiveMessageProps) {
  return (
    <>
      <div className="mailpoet_error mailpoet_install_premium_message">
        {MailPoet.I18n.t('premiumTabPremiumNotInstalledMessage')}
      </div>
      {props.url && (
        <Button href={props.url}>
          {MailPoet.I18n.t('premiumTabPremiumDownloadMessage')}
        </Button>
      )}
    </>
  );
}

type NotValidMessageProps = { message?: string };

function NotValidMessage({ message }: NotValidMessageProps) {
  return (
    <div className="mailpoet_error">
      {message || MailPoet.I18n.t('premiumTabPremiumKeyNotValidMessage')}
    </div>
  );
}

NotValidMessage.defaultProps = {
  message: '',
};

type Props = {
  keyMessage?: string;
  canUseSuccessClass: boolean;
};

export function PremiumMessages(props: Props) {
  const {
    premiumStatus: status,
    downloadUrl,
    activationUrl,
  } = useSelector('getKeyActivationState')();

  switch (status) {
    case PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE:
      return <ActiveMessage canUseSuccessClass={props.canUseSuccessClass} />;
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_INSTALLED:
      return <PremiumNotInstalledMessage url={downloadUrl} />;
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE:
      return <PremiumNotActiveMessage url={activationUrl} />;
    case PremiumStatus.INVALID:
      return <NotValidMessage message={props.keyMessage} />;
    default:
      return null;
  }
}

PremiumMessages.defaultProps = {
  keyMessage: '',
};
