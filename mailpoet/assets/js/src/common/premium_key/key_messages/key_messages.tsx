import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useSelector } from 'settings/store/hooks';

type KeyValidMessageProps = { canUseSuccessClass: boolean };

function KeyValidMessage({ canUseSuccessClass }: KeyValidMessageProps) {
  return (
    <div
      className={classnames('mailpoet_success_item', {
        mailpoet_success: canUseSuccessClass,
      })}
    >
      {__('Your key is valid', 'mailpoet')}
    </div>
  );
}

function KeyNotValidMessage() {
  return (
    <div className="mailpoet_error_item mailpoet_error">
      {__('Your key is not valid', 'mailpoet')}
    </div>
  );
}

type KeyMessagesProps = { canUseSuccessClass: boolean };

export function KeyMessages({ canUseSuccessClass }: KeyMessagesProps) {
  const { isKeyValid } = useSelector('getKeyActivationState')();
  return isKeyValid ? (
    <KeyValidMessage canUseSuccessClass={canUseSuccessClass} />
  ) : (
    <KeyNotValidMessage />
  );
}
