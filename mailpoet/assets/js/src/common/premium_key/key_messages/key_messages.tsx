import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { useSelector } from 'settings/store/hooks';

type KeyValidMessageProps = { canUseSuccessClass: boolean };
function KeyValidMessage({ canUseSuccessClass }: KeyValidMessageProps) {
  return (
    <div
      className={classnames('mailpoet_success_item mailpoet_success_item', {
        mailpoet_success: canUseSuccessClass,
      })}
    >
      {MailPoet.I18n.t('premiumTabKeyValidMessage')}
    </div>
  );
}

function KeyNotValidMessage() {
  return (
    <div className="mailpoet_error_item mailpoet_error">
      {MailPoet.I18n.t('premiumTabKeyNotValidMessage')}
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
