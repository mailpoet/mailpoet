import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { EmailTypeInfo } from 'email_editor/integrations/MailPoet/components/email_type_info';

export function EmailSettings() {
  // Render settings panels using PluginDocumentSettingPanel component
  return (
    <>
      <EmailTypeInfo />
      <PluginDocumentSettingPanel
        className="mailpoet-email-settings"
        title="Details"
        name="email-details"
      >
        <p>Hello here will be details settings</p>
      </PluginDocumentSettingPanel>
      <PluginDocumentSettingPanel
        className="mailpoet-email-template-settings"
        title="Template"
        name="email-template"
      >
        <p>Hello here will be template settings</p>
      </PluginDocumentSettingPanel>
    </>
  );
}
