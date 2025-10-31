import { EmailSettingsPanel } from './sidebar/email-settings-panel';
import { ContentSettingsPanel } from './sidebar/content-settings-panel';
import { InboxPreviewPanel } from './sidebar/inbox-preview-panel';
import { TrackingPanel } from './sidebar/tracking-panel';

export function EmailSidebarExtension() {
  return (
    <>
      <EmailSettingsPanel />
      <ContentSettingsPanel />
      <InboxPreviewPanel />
      <TrackingPanel />
    </>
  );
}
