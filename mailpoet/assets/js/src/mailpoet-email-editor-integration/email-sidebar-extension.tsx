import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { EmailActionsFill, TemplateSelection } from '@woocommerce/email-editor';
import { EmailSettingsPanel } from './sidebar/email-settings-panel';
import { InboxPreviewPanel } from './sidebar/inbox-preview-panel';
import { TrackingPanel } from './sidebar/tracking-panel';
import { SendPanel } from './send-panel/send-panel';
import { MAILPOET_EMAIL_POST_TYPE } from './constants';

function useIsEditingEmail(): boolean {
  const postType = useSelect(
    (select) => select(editorStore).getCurrentPostType(),
    [],
  );
  return postType === MAILPOET_EMAIL_POST_TYPE;
}

export function EmailSidebarExtension() {
  const isEditingEmail = useIsEditingEmail();

  // Don't render MailPoet panels when editing a template.
  // This also ensures correct panel ordering when switching back
  // to email editing, as all panels remount in the expected order.
  if (!isEditingEmail) {
    return null;
  }

  return (
    <>
      <EmailSettingsPanel />
      <InboxPreviewPanel />
      <TrackingPanel />
      <SendPanel />
    </>
  );
}

// Automation emails get their subject, sender and timing from the automation,
// so the Settings panel only shows the template.
export function AutomationEmailSidebarExtension() {
  const isEditingEmail = useIsEditingEmail();

  if (!isEditingEmail) {
    return null;
  }

  return (
    <EmailActionsFill>
      <TemplateSelection />
    </EmailActionsFill>
  );
}
