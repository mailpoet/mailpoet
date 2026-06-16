import { Card } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

/**
 * Renders how the email is likely to appear in a recipient's inbox: the sender,
 * the subject line and the preview text. Shared between the inbox preview
 * sidebar panel and the review & send panel.
 */
export function InboxPreviewCard() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    'mailpoet_data',
  );

  const senderName = (mailpoetEmailData?.sender_name as string) || '';
  const senderAddress = (mailpoetEmailData?.sender_address as string) || '';

  let fromAddress = __('(No sender)', 'mailpoet') as string;
  if (senderAddress) {
    fromAddress = senderName
      ? `${senderName} <${senderAddress}>`
      : senderAddress;
  }

  return (
    <Card className="mailpoet-inbox-preview-panel__card">
      <div className="mailpoet-inbox-preview-panel__from-address">
        {fromAddress}
      </div>
      <div className="mailpoet-inbox-preview-panel__subject">
        {mailpoetEmailData?.subject || __('(No subject)', 'mailpoet')}
      </div>
      <div className="mailpoet-inbox-preview-panel__preheader">
        {mailpoetEmailData?.preheader || __('(No preview text)', 'mailpoet')}
      </div>
    </Card>
  );
}
