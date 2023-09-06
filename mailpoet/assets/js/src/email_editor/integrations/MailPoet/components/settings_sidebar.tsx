import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';

export function SettingsSidebar() {
  // Render email settings panels using PluginDocumentSettingPanel component
  return (
    <PluginDocumentSettingPanel
      className="mailpoet-email-editor-setting-panel"
      title={__('Details', 'mailpoet')}
      name="mailpoet-email-editor-setting-panel"
    >
      <p>{__('There should be subject and preview text', 'mailpoet')}</p>
    </PluginDocumentSettingPanel>
  );
}
