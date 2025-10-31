import {
  // @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
  PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { ScheduledRow } from './components/scheduled-row';
import { RecipientsRow } from './components/recipients-row';

export function EmailSettingsPanel() {
  return (
    <PluginDocumentSettingPanel
      name="mailpoet-email-settings"
      title={__('Email settings', 'mailpoet')}
      className="mailpoet-email-settings-panel"
    >
      <ScheduledRow />
      <RecipientsRow />
    </PluginDocumentSettingPanel>
  );
}
