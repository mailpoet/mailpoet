import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { useSelector } from 'settings/store/hooks';
import { PremiumStatus } from 'settings/store/types';
import { Button } from 'common/button/button';
import { PremiumModal } from 'common/premium-modal';
import { useState } from 'react';
import { Data } from '../../premium-modal/upgrade-info';

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

type PremiumMessageProps = {
  data?: Data;
  message?: string;
  buttonText: string;
};

function PremiumMessageWithModal(props: PremiumMessageProps) {
  const [showPremiumModal, setShowPremiumModal] = useState(false);

  return (
    <>
      {props.message && (
        <div className="mailpoet_error mailpoet_install_premium_message">
          {props.message}
        </div>
      )}
      <Button
        onClick={(e) => {
          e.preventDefault();
          setShowPremiumModal(true);
        }}
      >
        {props.buttonText}
      </Button>
      {showPremiumModal && (
        <PremiumModal
          data={props.data}
          onRequestClose={() => {
            setShowPremiumModal(false);
          }}
        >
          {__(
            'Your current MailPoet plan includes advanced features, but they require the MailPoet Premium plugin to be installed and activated.',
            'mailpoet',
          )}
        </PremiumModal>
      )}
    </>
  );
}

type NotValidMessageProps = { message?: string };

function NotValidMessage({ message }: NotValidMessageProps) {
  return (
    <div className="mailpoet_error">
      {message
        ? createInterpolateElement(message, {
            a: (
              <a
                aria-label={message}
                className="mailpoet-link"
                href="https://kb.mailpoet.com/article/249-how-to-change-the-domain-associated-with-a-key"
                rel="noopener noreferrer"
                target="_blank"
              >
                &nbsp;
              </a>
            ),
          })
        : __('Your key is not valid for MailPoet Premium', 'mailpoet')}
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

function PremiumMessages(props: Props) {
  const { premiumStatus: status } = useSelector('getKeyActivationState')();

  switch (status) {
    case PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE:
      return <ActiveMessage canUseSuccessClass={props.canUseSuccessClass} />;
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_INSTALLED:
      return (
        <PremiumMessageWithModal
          data={{
            premiumInstalled: false,
            premiumActive: false,
            hasValidApiKey: true,
            hasValidPremiumKey: true,
          }}
          message={__('MailPoet Premium is not installed.', 'mailpoet')}
          buttonText={__('Download MailPoet Premium plugin', 'mailpoet')}
        />
      );
    case PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE:
      return (
        <PremiumMessageWithModal
          data={{
            premiumInstalled: true,
            premiumActive: false,
            hasValidApiKey: true,
            hasValidPremiumKey: true,
          }}
          message={__(
            'MailPoet Premium is installed but not activated.',
            'mailpoet',
          )}
          buttonText={__('Activate MailPoet Premium plugin', 'mailpoet')}
        />
      );
    case PremiumStatus.INVALID:
      return <NotValidMessage message={props.keyMessage} />;
    default:
      return null;
  }
}

PremiumMessages.defaultProps = {
  keyMessage: '',
};

export { PremiumMessages, PremiumMessageWithModal };
