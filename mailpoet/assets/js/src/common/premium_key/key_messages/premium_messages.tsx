import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
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
      {__('MailPoet Premium is active', 'mailpoet')}
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
        {__('MailPoet Premium is installed but not activated.', 'mailpoet')}
      </div>
      {props.url && (
        <Button href={props.url}>
          {__('Activate MailPoet Premium plugin', 'mailpoet')}
        </Button>
      )}
    </>
  );
}

function PremiumNotInstalledMessage(props: PremiumNotActiveMessageProps) {
  return (
    <>
      <div className="mailpoet_error mailpoet_install_premium_message">
        {__('MailPoet Premium is not installed.', 'mailpoet')}
      </div>
      {props.url && (
        <Button href={props.url}>
          {__('Download MailPoet Premium plugin', 'mailpoet')}
        </Button>
      )}
    </>
  );
}

type NotValidMessageProps = { message?: string };

function NotValidMessage({ message }: NotValidMessageProps) {
  return (
    <div className="mailpoet_error">
      {message || __('Your key is not valid for MailPoet Premium', 'mailpoet')}
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
