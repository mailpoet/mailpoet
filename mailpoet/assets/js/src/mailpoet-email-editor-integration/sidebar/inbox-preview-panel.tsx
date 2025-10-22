import { Card } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
  // @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
  PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

export function InboxPreviewPanel() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const siteData = useSelect((select) => {
    // @ts-expect-error getSite is not typed
    const site = select('core').getSite();
    return {
      title: (site?.title as string) || '',
      email: (site?.email as string) || '',
    };
  }, []);

  return (
    <PluginDocumentSettingPanel
      name="mailpoet-inbox-preview"
      title={__('Inbox preview', 'mailpoet')}
      className="mailpoet-inbox-preview-panel"
    >
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
    </PluginDocumentSettingPanel>
  );
}
