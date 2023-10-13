import { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import {
  KeyActivationState,
  MssStatus,
  PremiumStatus,
} from 'settings/store/types';
import {
  KeyMessages,
  MssMessages,
  PremiumMessages,
  ServiceUnavailableMessage,
  AccessRestrictedMessages,
} from './key-messages';
import {
  ClickToRefresh,
  PendingApprovalBody,
  PendingApprovalTitle,
} from '../pending-approval-notice';

export function Messages(
  state: KeyActivationState,
  showPendingApprovalNotice: boolean,
  activationCallback: () => Promise<void>,
  verifyKey: () => Promise<void>,
  showRefreshMessage: boolean,
) {
  if (state.code === 503) {
    return (
      <div className="key-activation-messages">
        <ServiceUnavailableMessage />
      </div>
    );
  }
  const onRefreshClick = async (e: MouseEvent<HTMLAnchorElement>) => {
    e.preventDefault();
    await verifyKey();
  };

  return (
    <div className="key-activation-messages">
      <KeyMessages canUseSuccessClass={!showPendingApprovalNotice} />
      {state.mssStatus !== null && (
        <MssMessages
          keyMessage={state.mssMessage}
          activationCallback={activationCallback}
          canUseSuccessClass={!showPendingApprovalNotice}
        />
      )}
      {state.congratulatoryMssEmailSentTo && (
        <div className="mailpoet_success_item mailpoet_success">
          {__('A test email was sent to [email_address]', 'mailpoet').replace(
            '[email_address]',
            state.congratulatoryMssEmailSentTo,
          )}
        </div>
      )}
      {state.premiumStatus !== null && (
        <PremiumMessages
          keyMessage={state.premiumMessage}
          canUseSuccessClass={!showPendingApprovalNotice}
        />
      )}

      {(state.premiumStatus === PremiumStatus.VALID_UNDERPRIVILEGED ||
        state.mssStatus === MssStatus.VALID_UNDERPRIVILEGED) && (
        <AccessRestrictedMessages />
      )}

      {showPendingApprovalNotice && (
        <div>
          <div className="pending_approval_heading mailpoet_error">
            <PendingApprovalTitle />
          </div>
          <p>
            <PendingApprovalBody />
          </p>
          {showRefreshMessage ? (
            <p>
              <ClickToRefresh onRefreshClick={onRefreshClick} />
            </p>
          ) : null}
        </div>
      )}

      {!state.isKeyValid && (
        <p>
          <a
            href="https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key"
            target="_blank"
            rel="noopener noreferrer"
            className="mailpoet_error"
          >
            {__('Learn more', 'mailpoet')}
          </a>
        </p>
      )}
    </div>
  );
}
