import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Notice } from '@wordpress/ui';
import { extractEmailDomain } from 'common/functions';
import { AuthorizeSenderEmailAndDomainModal } from 'common/authorize-sender-email-and-domain-modal';
import { SenderRestrictionsType } from 'common/sender-domain-notice';
import { SenderDomainNoticeBody } from 'common/sender-domain-notice/sender-domain-notice-body';
import { UseSenderFields } from '../shared/use-sender-fields';
import { getSenderAuthorizationState } from './sender-notice-utils';

const SPF_DKIM_DMARC_KB_URL =
  'https://kb.mailpoet.com/article/295-spf-dkim-dmarc';

type ModalTab = 'sender_email' | 'sender_domain';

type Props = {
  senderFields: UseSenderFields;
};

export function SenderAuthorizationNotice({
  senderFields,
}: Props): JSX.Element | null {
  const [modalTab, setModalTab] = useState<ModalTab | null>(null);

  const senderRestrictions: SenderRestrictionsType | undefined =
    window.mailpoet_sender_restrictions;
  const { senderAddress, isPartiallyVerifiedDomain } = senderFields;

  const { showEmailAuthorization, showDomainWarning } =
    getSenderAuthorizationState(senderFields);

  if (!showEmailAuthorization && !showDomainWarning) {
    return null;
  }

  const lowerLimit = senderRestrictions?.lowerLimit || 500;
  const isSmallSender = window.mailpoet_subscribers_count <= lowerLimit;
  const alwaysRewrite = Boolean(senderRestrictions?.alwaysRewrite);
  const isAlert = !(
    isSmallSender ||
    isPartiallyVerifiedDomain ||
    alwaysRewrite
  );
  const showRewrittenEmail =
    (isSmallSender || alwaysRewrite) && !isPartiallyVerifiedDomain;
  const rewrittenEmail = `${senderAddress.replace(
    '@',
    '=',
  )}@replies.sendingservice.net`;

  return (
    <>
      {showEmailAuthorization && (
        <Notice.Root intent="warning">
          <Notice.Description>
            {__('Not an authorized sender email address.', 'mailpoet')}
          </Notice.Description>
          <Notice.Actions>
            <Notice.ActionButton onClick={() => setModalTab('sender_email')}>
              {__('Authorize it now', 'mailpoet')}
            </Notice.ActionButton>
          </Notice.Actions>
        </Notice.Root>
      )}

      {showDomainWarning && (
        <Notice.Root intent={isAlert ? 'warning' : 'info'}>
          {showRewrittenEmail && (
            <Notice.Title>
              {__('Will be sent as:', 'mailpoet')} {rewrittenEmail}
            </Notice.Title>
          )}
          <Notice.Description>
            <SenderDomainNoticeBody
              emailAddressDomain={extractEmailDomain(senderAddress)}
              isFreeDomain={false}
              isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
              isSmallSender={isSmallSender || alwaysRewrite}
            />
          </Notice.Description>
          <Notice.Actions>
            <Notice.ActionButton onClick={() => setModalTab('sender_domain')}>
              {isPartiallyVerifiedDomain
                ? __('Update settings', 'mailpoet')
                : __('Authenticate', 'mailpoet')}
            </Notice.ActionButton>
            <Notice.ActionLink href={SPF_DKIM_DMARC_KB_URL} openInNewTab>
              {__('Learn more', 'mailpoet')}
            </Notice.ActionLink>
          </Notice.Actions>
        </Notice.Root>
      )}

      {modalTab && (
        <AuthorizeSenderEmailAndDomainModal
          senderEmail={senderAddress}
          onRequestClose={() => setModalTab(null)}
          showSenderEmailTab={showEmailAuthorization}
          showSenderDomainTab={showDomainWarning}
          initialTab={modalTab}
          onSuccessAction={senderFields.handleSuccessfulAuthorization}
          autoSwitchTab={setModalTab}
        />
      )}
    </>
  );
}
