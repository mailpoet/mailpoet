import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

export function EmailSettings() {
  // Render settings panels using PluginDocumentSettingPanel component
  return (
    <>
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
