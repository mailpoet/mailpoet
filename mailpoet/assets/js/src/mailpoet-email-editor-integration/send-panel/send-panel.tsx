import { Button, PanelBody, Notice, Fill } from '@wordpress/components';
import { Button as UiButton, Text, Stack } from '@wordpress/ui';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import { storeName as emailEditorStore } from '@woocommerce/email-editor';
import { SCHEDULE_MODE_SUBSCRIBER_TIMEZONE } from 'common/newsletter-schedule-mode';
import { InboxPreviewCard } from '../shared/inbox-preview-card';
import { useScheduledDate } from '../shared/use-scheduled-date';
import { useRecipients } from '../shared/use-recipients';
import { ScheduledDatePicker } from '../shared/scheduled-date-picker';
import { ScheduleModeControls } from '../shared/schedule-mode-controls';
import { RecipientsSelector } from '../shared/recipients-selector';
import { store as emailEditorIntegrationStore } from '../store';
import { useSendEmail } from './use-send-email';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';
import { useSenderFields } from '../shared/use-sender-fields';
import { SenderControls } from './sender-controls';

export function SendPanel() {
  const isOpen = useSelect(
    (select) => select(emailEditorIntegrationStore).isSendPanelOpen(),
    [],
  );
  const postId = useSelect(
    (select) => select(editorStore).getCurrentPostId(),
    [],
  );
  const { closeSendPanel } = useDispatch(emailEditorIntegrationStore);
  const { setEmailPost, togglePreviewModal } = useDispatch(emailEditorStore);

  const { isScheduled, formattedDate, setScheduledDate, scheduleMode } =
    useScheduledDate();
  const isSubscriberTimezoneMode =
    scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;
  const recipients = useRecipients();
  const {
    recipientLabel,
    totalRecipientCount,
    isLoadingSegments,
    isLoadingRecipientCount,
  } = recipients;
  const { sendEmail, isSending, error, clearError } = useSendEmail();
  const senderFields = useSenderFields();
  const { hasValidationErrors, validateSenderFields } = senderFields;

  const hasNoRecipients =
    !isLoadingSegments && !isLoadingRecipientCount && totalRecipientCount === 0;

  const handleSendTestEmail = useCallback(() => {
    if (postId) {
      setEmailPost(postId, MAILPOET_EMAIL_POST_TYPE);
      togglePreviewModal(true);
    }
  }, [postId, setEmailPost, togglePreviewModal]);

  const handleCancel = useCallback(() => {
    clearError();
    void closeSendPanel();
  }, [clearError, closeSendPanel]);

  const handleSend = useCallback(async () => {
    const isValid = await validateSenderFields();
    if (!isValid) {
      return;
    }
    await sendEmail();
  }, [sendEmail, validateSenderFields]);

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
      <div className="mailpoet-send-panel">
        <Stack
          className="mailpoet-send-panel__header"
          direction="row"
          justify="space-between"
        >
          <Button
            variant="secondary"
            size="compact"
            disabled={isSending}
            onClick={handleCancel}
          >
            {__('Cancel', 'mailpoet')}
          </Button>
          <Button
            variant="primary"
            size="compact"
            isBusy={isSending}
            disabled={isSending || hasNoRecipients || hasValidationErrors}
            onClick={() => void handleSend()}
            data-automation-id="email_send_panel_send_button"
          >
            {sendButtonLabel}
          </Button>
        </Stack>

        <div className="mailpoet-send-panel__content">
          <PanelBody>
            <strong>{title}</strong>
            <p>{subtitle}</p>
            <InboxPreviewCard />
          </PanelBody>

          <SenderControls senderFields={senderFields} />

          <PanelBody
            title={sprintf(
              /* translators: %s is the formatted send date or "Immediately". */
              __('Send: %s', 'mailpoet'),
              formattedDate,
            )}
            initialOpen={false}
          >
            <Stack direction="column" gap="lg">
              <Stack direction="row" align="center" justify="space-between">
                <Text
                  variant="heading-md"
                  /* eslint-disable-next-line jsx-a11y/heading-has-content */
                  render={<h3 />}
                >
                  {__('Send', 'mailpoet')}
                </Text>
                {!isSubscriberTimezoneMode && (
                  <Button
                    size="small"
                    variant="tertiary"
                    onClick={() => setScheduledDate(null)}
                  >
                    {__('Now', 'mailpoet')}
                  </Button>
                )}
              </Stack>
              <ScheduleModeControls />
              {!isSubscriberTimezoneMode && <ScheduledDatePicker />}
            </Stack>
          </PanelBody>

          <PanelBody
            className="mailpoet-send-panel__recipients"
            title={sprintf(
              /* translators: %s is the recipient segment label. */
              __('Recipients: %s', 'mailpoet'),
              recipientLabel,
            )}
            initialOpen={false}
          >
            <RecipientsSelector recipients={recipients} />
          </PanelBody>

          <div className="mailpoet-send-panel__test-email">
            <UiButton
              variant="minimal"
              className="mailpoet-send-panel__test-email-button"
              onClick={handleSendTestEmail}
            >
              {__('Send a test email', 'mailpoet')}
            </UiButton>
            {hasNoRecipients && (
              <Text
                variant="body-sm"
                className="mailpoet-send-panel__no-recipients"
              >
                {__(
                  'No subscribers yet. You can still send a test email.',
                  'mailpoet',
                )}
              </Text>
            )}
          </div>

          {error && (
            <Notice
              status="error"
              className="mailpoet-send-panel__notice"
              onRemove={clearError}
            >
              {error}
            </Notice>
          )}
        </div>
      </div>
    </Fill>
  );
}
