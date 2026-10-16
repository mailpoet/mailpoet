import { EmailActionsFill, TemplateSelection } from '@woocommerce/email-editor';
import { ScheduledRow } from './components/scheduled-row';
import { RecipientsRow } from './components/recipients-row';
import { ArchiveVisibilityRow } from './components/archive-visibility-row';
import { SubjectFields } from './components/subject-fields';

export function EmailSettingsPanel() {
  return (
    <EmailActionsFill>
      <SubjectFields />
      <ScheduledRow />
      <RecipientsRow />
      <ArchiveVisibilityRow />
      <TemplateSelection />
    </EmailActionsFill>
  );
}
