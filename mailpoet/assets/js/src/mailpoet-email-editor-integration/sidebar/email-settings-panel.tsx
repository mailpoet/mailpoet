import { EmailActionsFill, TemplateSelection } from '@woocommerce/email-editor';
import { ScheduledRow } from './components/scheduled-row';
import { RecipientsRow } from './components/recipients-row';

export function EmailSettingsPanel() {
  return (
    <EmailActionsFill>
      <ScheduledRow />
      <RecipientsRow />
      <TemplateSelection />
    </EmailActionsFill>
  );
}
