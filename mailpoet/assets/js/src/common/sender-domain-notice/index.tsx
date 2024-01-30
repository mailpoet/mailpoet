import { escapeHTML } from '@wordpress/escape-html';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { extractEmailDomain } from 'common/functions';
import { InlineNotice } from 'common/notices/inline-notice';
import { SenderDomainNoticeBody } from './sender-domain-notice-body';
import { SenderActions } from './sender-domain-notice-actions';

export type SenderRestrictionsType = {
  lowerLimit: number;
  isAuthorizedDomainRequiredForNewCampaigns?: boolean;
  campaignTypes?: string[];
  alwaysRewrite?: boolean;
};

type SenderDomainInlineNoticeProps = {
  authorizeAction: (e) => void;
  emailAddress: string;
  subscribersCount: number;
  isFreeDomain: boolean;
  isPartiallyVerifiedDomain: boolean;
  senderRestrictions: SenderRestrictionsType;
};

function SenderEmailRewriteInfo({ emailAddress = '' }): JSX.Element {
  const rewrittenEmail = `${emailAddress.replace(
    '@',
    '=',
  )}@replies.sendingservice.net`;

  return (
    <p>
      {createInterpolateElement(
        __('Will be sent as: <rewrittenFromEmail/>', 'mailpoet'),
        {
          rewrittenFromEmail: <strong>{escapeHTML(rewrittenEmail)}</strong>,
        },
      )}
    </p>
  );
}

function SenderDomainInlineNotice({
  emailAddress,
  authorizeAction,
  subscribersCount,
  isFreeDomain,
  isPartiallyVerifiedDomain,
  senderRestrictions,
}: SenderDomainInlineNoticeProps) {
  let showRewrittenEmail = false;
  const showAuthorizeButton = !isFreeDomain;
  let isAlert = true;

  const emailAddressDomain = extractEmailDomain(emailAddress);

  const LOWER_LIMIT = senderRestrictions?.lowerLimit || 500;

  const isSmallSender = subscribersCount <= LOWER_LIMIT;

  if (
    isSmallSender ||
    isPartiallyVerifiedDomain ||
    senderRestrictions.alwaysRewrite
  ) {
    isAlert = false;
  }

  if (
    (isSmallSender || senderRestrictions.alwaysRewrite) &&
    !isPartiallyVerifiedDomain
  ) {
    showRewrittenEmail = true;
  }

  return (
    <InlineNotice
      status={isAlert ? 'alert' : 'info'}
      topMessage={
        showRewrittenEmail ? (
          <SenderEmailRewriteInfo emailAddress={emailAddress} />
        ) : undefined
      }
      actions={
        <SenderActions
          showAuthorizeButton={showAuthorizeButton}
          authorizeAction={authorizeAction}
          isFreeDomain={isFreeDomain}
          isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
        />
      }
    >
      <SenderDomainNoticeBody
        emailAddressDomain={emailAddressDomain}
        isFreeDomain={isFreeDomain}
        isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
        isSmallSender={isSmallSender}
      />
    </InlineNotice>
  );
}

export { SenderDomainInlineNotice };
