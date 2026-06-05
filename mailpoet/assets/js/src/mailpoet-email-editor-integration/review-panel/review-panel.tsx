import {
  Button,
  PanelBody,
  Notice,
  Fill,
  __experimentalHStack as HStack,
  __experimentalVStack as VStack,
  __experimentalHeading as Heading,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import { storeName as emailEditorStore } from '@woocommerce/email-editor';
import { InboxPreviewCard } from '../shared/inbox-preview-card';
import { useScheduledDate } from '../shared/use-scheduled-date';
import { useRecipients } from '../shared/use-recipients';
import { ScheduledDatePicker } from '../shared/scheduled-date-picker';
import { RecipientsSelector } from '../shared/recipients-selector';
import { store as emailEditorIntegrationStore } from '../store';
import { useSendEmail } from './use-send-email';

export function ReviewPanel() {
  const isOpen = useSelect(
    (select) => select(emailEditorIntegrationStore).isReviewPanelOpen(),
    [],
  );
  const postId = useSelect(
    (select) => select(editorStore).getCurrentPostId(),
    [],
  );
  const { closeReviewPanel } = useDispatch(emailEditorIntegrationStore);
  const { setEmailPost, togglePreviewModal } = useDispatch(emailEditorStore);

  const { isScheduled, formattedDate, setScheduledDate } = useScheduledDate();
  const recipients = useRecipients();
  const {
    recipientLabel,
    totalRecipientCount,
    isLoadingSegments,
    isLoadingRecipientCount,
  } = recipients;
  const { sendEmail, isSending, error, clearError } = useSendEmail();

  const hasNoRecipients =
    !isLoadingSegments && !isLoadingRecipientCount && totalRecipientCount === 0;

  const handleSendTestEmail = useCallback(() => {
    if (postId) {
      setEmailPost(postId, 'mailpoet_email');
      togglePreviewModal(true);
    }
  }, [postId, setEmailPost, togglePreviewModal]);

  if (!isOpen) {
    return null;
  }

  const title = isScheduled
    ? __('Are you ready to schedule?', 'mailpoet')
    : __('Are you ready to send?', 'mailpoet');
  const subtitle = isScheduled
    ? __('Your email will be sent at the specified date and time.', 'mailpoet')
    : __('Your email will be sent immediately.', 'mailpoet');
  const sendButtonLabel = isScheduled
    ? __('Schedule', 'mailpoet')
    : __('Send', 'mailpoet');

  return (
    <Fill name="ComplementaryArea/core">
      <div className="mailpoet-review-panel">
        <HStack
          className="mailpoet-review-panel__header"
          justify="space-between"
        >
          <Button
            variant="secondary"
            size="compact"
            disabled={isSending}
            onClick={() => void closeReviewPanel()}
          >
            {__('Cancel', 'mailpoet')}
          </Button>
          <Button
            variant="primary"
            size="compact"
            isBusy={isSending}
            disabled={isSending || hasNoRecipients}
            onClick={() => void sendEmail()}
          >
            {sendButtonLabel}
          </Button>
        </HStack>

        <div className="mailpoet-review-panel__content">
          <PanelBody>
            <strong>{title}</strong>
            <p>{subtitle}</p>
            <InboxPreviewCard />
          </PanelBody>

          <PanelBody
            title={sprintf(
              /* translators: %s is the formatted send date or "Immediately". */
              __('Send: %s', 'mailpoet'),
              formattedDate,
            )}
            initialOpen={false}
          >
            <VStack spacing={4}>
              <HStack alignment="center">
                <Heading level={2} size={13}>
                  {__('Send', 'mailpoet')}
                </Heading>
                <Spacer />
                <Button
                  size="small"
                  variant="tertiary"
                  onClick={() => setScheduledDate(null)}
                >
                  {__('Now', 'mailpoet')}
                </Button>
              </HStack>
              <ScheduledDatePicker />
            </VStack>
          </PanelBody>

          <PanelBody
            title={sprintf(
              /* translators: %s is the recipient segment label. */
              __('Recipients: %s', 'mailpoet'),
              recipientLabel,
            )}
            initialOpen={false}
          >
            <RecipientsSelector recipients={recipients} />
          </PanelBody>

          <div className="mailpoet-review-panel__test-email">
            <Button variant="link" onClick={handleSendTestEmail}>
              {__('Send a test email', 'mailpoet')}
            </Button>
          </div>

          {error && (
            <Notice
              status="error"
              className="mailpoet-review-panel__notice"
              onRemove={clearError}
            >
              {error}
            </Notice>
          )}

          {hasNoRecipients && (
            <Notice
              status="warning"
              className="mailpoet-review-panel__notice"
              isDismissible={false}
            >
              {__(
                'No subscribers yet. You can still send a test email.',
                'mailpoet',
              )}
            </Notice>
          )}
        </div>
      </div>
    </Fill>
  );
}
