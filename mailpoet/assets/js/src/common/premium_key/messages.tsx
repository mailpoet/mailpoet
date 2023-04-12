import { MouseEvent } from 'react';
import ReactStringReplace from 'react-string-replace';
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
} from './key_messages';
import { getLinkRegex } from '../utils';

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
            {__(
              'Note: this subscription is currently pending approval by MailPoet.',
              'mailpoet',
            )}
          </div>
          <div>
            {__(
              'You should receive an email from us about it within 48h. Sending will be paused in the meantime, but you can still send email previews to yourself and explore the plugin features.',
              'mailpoet',
            )}
          </div>
          {showRefreshMessage ? (
            <div>
              {ReactStringReplace(
                __(
                  'If you have already received approval email, click [link]here[/link] to update the status.',
                  'mailpoet',
                ),
                getLinkRegex(),
                (match) => (
                  <a onClick={onRefreshClick} href="#">
                    {match}
                  </a>
                ),
              )}
            </div>
          ) : null}
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
            {__('Learn more', 'mailpoet')}
          </a>
        </p>
      )}
    </div>
  );
}
