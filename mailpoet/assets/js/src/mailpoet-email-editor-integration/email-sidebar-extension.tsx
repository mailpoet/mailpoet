import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { EmailSettingsPanel } from './sidebar/email-settings-panel';
import { ContentSettingsPanel } from './sidebar/content-settings-panel';
import { InboxPreviewPanel } from './sidebar/inbox-preview-panel';
import { TrackingPanel } from './sidebar/tracking-panel';

export function EmailSidebarExtension() {
  const postType = useSelect(
    (select) => select(editorStore).getCurrentPostType(),
    [],
  );

  // Don't render MailPoet panels when editing a template.
  // This also ensures correct panel ordering when switching back
  // to email editing, as all panels remount in the expected order.
  if (postType !== 'mailpoet_email') {
    return null;
  }

  return (
    <>
      <EmailSettingsPanel />
      <ContentSettingsPanel />
      <InboxPreviewPanel />
      <TrackingPanel />
    </>
  );
}
