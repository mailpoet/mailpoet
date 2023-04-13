import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useSelector } from 'settings/store/hooks';
import { MssStatus } from 'settings/store/types';

type MssActiveMessageProps = { canUseSuccessClass: boolean };

function MssActiveMessage({ canUseSuccessClass }: MssActiveMessageProps) {
  return (
    <div
      className={classnames('mailpoet_success_item mailpoet_mss_key_valid', {
        mailpoet_success: canUseSuccessClass,
      })}
    >
      {__('MailPoet Sending Service is active', 'mailpoet')}
    </div>
  );
}

type NotValidMessageProps = { message?: string };

function NotValidMessage({ message }: NotValidMessageProps) {
  return (
    <div className="mailpoet_error">
      {message ||
        __(
          'Your key is not valid for the MailPoet Sending Service',
          'mailpoet',
        )}
    </div>
  );
}

NotValidMessage.defaultProps = {
  message: '',
};

type MssNotActiveMessageProps = { activationCallback?: () => void };

function MssNotActiveMessage({ activationCallback }: MssNotActiveMessageProps) {
  return (
    <div className="mailpoet_error">
      {__('MailPoet Sending Service is not active.', 'mailpoet')}{' '}
      {activationCallback && (
        <button
          type="button"
          className="mailpoet-button button button-primary button-small"
          onClick={activationCallback}
        >
          {__('Activate MailPoet Sending Service', 'mailpoet')}
        </button>
      )}
    </div>
  );
}

type Props = {
  keyMessage?: string;
  activationCallback: () => void;
  canUseSuccessClass: boolean;
};

export function MssMessages(props: Props) {
  const { mssStatus, mssAccessRestriction } = useSelector(
    'getKeyActivationState',
  )();
  switch (mssStatus) {
    case MssStatus.VALID_MSS_ACTIVE:
      return <MssActiveMessage canUseSuccessClass={props.canUseSuccessClass} />;
    case MssStatus.VALID_MSS_NOT_ACTIVE:
      return (
        <MssNotActiveMessage activationCallback={props.activationCallback} />
      );
    case MssStatus.INVALID:
      return <NotValidMessage message={props.keyMessage} />;

    case MssStatus.VALID_UNDERPRIVILEGED:
      if (
        mssAccessRestriction &&
        mssAccessRestriction !== 'insufficient_privileges'
      ) {
        return <MssNotActiveMessage />;
      }
      return null;

    default:
      return null;
  }
}

MssMessages.defaultProps = {
  keyMessage: '',
};
