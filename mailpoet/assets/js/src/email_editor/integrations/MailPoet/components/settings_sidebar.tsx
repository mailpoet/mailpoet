import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { EmailTypeInfo } from './email_type_info';

export function SettingsSidebar() {
  // Render email settings panels using PluginDocumentSettingPanel component
  return (
    <>
      <EmailTypeInfo />
      <PluginDocumentSettingPanel
        className="mailpoet-email-editor-setting-panel"
        title={__('Details', 'mailpoet')}
        name="mailpoet-email-editor-setting-panel"
      >
        <p>{__('There should be subject and preview text', 'mailpoet')}</p>
      </PluginDocumentSettingPanel>
    </>
  );
}
