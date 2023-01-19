import { KeyActivationState } from 'settings/store/types';
import { MailPoet } from 'mailpoet';
import {
  ServiceUnavailableMessage,
  KeyMessages,
  MssMessages,
  PremiumMessages,
} from './key_messages';

export function Messages(
  state: KeyActivationState,
  showPendingApprovalNotice: boolean,
  activationCallback: () => Promise<void>,
) {
  if (state.code === 503) {
    return (
      <div className="key-activation-messages">
        <ServiceUnavailableMessage />
      </div>
    );
  }

  return (
    <div className="key-activation-messages">
      <KeyMessages />
      {state.mssStatus !== null && (
        <MssMessages
          keyMessage={state.mssMessage}
          activationCallback={activationCallback}
        />
      )}
      {state.congratulatoryMssEmailSentTo && (
        <div className="mailpoet_success_item mailpoet_success">
          {MailPoet.I18n.t('premiumTabCongratulatoryMssEmailSent').replace(
            '[email_address]',
            state.congratulatoryMssEmailSentTo,
          )}
        </div>
      )}
      {state.premiumStatus !== null && (
        <PremiumMessages keyMessage={state.premiumMessage} />
      )}

      {showPendingApprovalNotice && (
        <div className="mailpoet_success">
          <div className="pending_approval_heading">
            {MailPoet.I18n.t('premiumTabPendingApprovalHeading')}
          </div>
          <div>{MailPoet.I18n.t('premiumTabPendingApprovalMessage')}</div>
        </div>
      )}

      {!state.isKeyValid && (
        <p>
          <a
            href="https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key"
            target="_blank"
            rel="noopener noreferrer"
            data-beacon-article="5ef1da9d2c7d3a10cba966c5"
            className="mailpoet_error"
          >
            {MailPoet.I18n.t('learnMore')}
          </a>
        </p>
      )}
    </div>
  );
}
