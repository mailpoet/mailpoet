import { EmailStatus } from './sidebar/email-status';
import { ContentSettingsPanel } from './sidebar/content-settings-panel';
import { InboxPreviewPanel } from './sidebar/inbox-preview-panel';
import { TrackingPanel } from './sidebar/tracking-panel';

export function EmailSidebarExtension() {
  return (
    <>
      <EmailStatus />
      <ContentSettingsPanel />
      <InboxPreviewPanel />
      <TrackingPanel />
    </>
  );
}
