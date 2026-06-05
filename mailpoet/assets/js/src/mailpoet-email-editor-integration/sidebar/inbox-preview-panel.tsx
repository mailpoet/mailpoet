import {
  // @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
  PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { InboxPreviewCard } from '../shared/inbox-preview-card';

export function InboxPreviewPanel() {
  return (
    <PluginDocumentSettingPanel
      name="mailpoet-inbox-preview"
      title={__('Inbox preview', 'mailpoet')}
      className="mailpoet-inbox-preview-panel"
    >
      <InboxPreviewCard />
    </PluginDocumentSettingPanel>
  );
}
