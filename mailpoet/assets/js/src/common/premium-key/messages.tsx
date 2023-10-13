import { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
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
            {createInterpolateElement(
              __(
                'MailPoet is <link>reviewing your subscription</link>.',
                'mailpoet',
              ),
              {
                link: (
                  // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                  <a
                    href="https://kb.mailpoet.com/article/379-our-approval-process"
                    target="_blank"
                    rel="noreferrer"
                  />
                ),
              },
            )}
          </div>
          <p>
            {createInterpolateElement(
              __(
                `You can use all MailPoet features and send <link1>email previews</link1> to your <link2>authorized email addresses</link2>, but sending to your email list contacts is temporarily paused until we review your subscription. If you don't hear from us within 48 hours, please check the inbox and spam folders of your MailPoet account email for follow-up emails with the subject "<emailSubject/>" and reply, or <link3>contact us</link3>.`,
                'mailpoet',
              ),
              {
                link1: (
                  // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                  <a
                    href="https://kb.mailpoet.com/article/290-check-your-newsletter-before-sending-it"
                    target="_blank"
                    rel="noreferrer"
                  />
                ),
                link2: (
                  // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                  <a
                    href="https://kb.mailpoet.com/article/266-how-to-add-an-authorized-email-address-as-the-from-address#how-to-authorize-an-email-address"
                    target="_blank"
                    rel="noreferrer"
                  />
                ),
                link3: (
                  // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                  <a
                    href="https://www.mailpoet.com/support/"
                    target="_blank"
                    rel="noreferrer"
                  />
                ),
                emailSubject: <>Your MailPoet Subscription Review</>,
              },
            )}
          </p>
          {showRefreshMessage ? (
            <p>
              {createInterpolateElement(
                __(
                  `If you have already received approval email, click <link>here</link> to update the status.`,
                  'mailpoet',
                ),
                {
                  // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                  link: <a onClick={onRefreshClick} href="#" />,
                },
              )}
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
