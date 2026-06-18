import { Card } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

function formatSender(name = '', address = ''): string {
  if (name && address) {
    return `${name} <${address}>`;
  }
  return name || address || __('(No sender)', 'mailpoet');
}

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

  return (
    <Card className="mailpoet-inbox-preview-panel__card">
      <div className="mailpoet-inbox-preview-panel__from-address">
        {formatSender(senderName, senderAddress)}
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
