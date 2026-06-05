import { Card } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Renders how the email is likely to appear in a recipient's inbox: the sender,
 * the subject line and the preview text. Shared between the inbox preview
 * sidebar panel and the review & send panel.
 */
export function InboxPreviewCard() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const siteData = useSelect((select) => {
    const site = select('core').getSite();
    return {
      title: (site?.title as string) || '',
      email: (site?.email as string) || '',
    };
  }, []);

  return (
    <Card className="mailpoet-inbox-preview-panel__card">
      <div className="mailpoet-inbox-preview-panel__from-address">
        {siteData.title && siteData.email
          ? `${siteData.title} <${siteData.email}>`
          : __('(No sender)', 'mailpoet')}
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
