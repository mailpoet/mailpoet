import { useCallback, useEffect, useRef, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Form } from 'form/form.jsx';
import { SubscribersLimitNotice } from 'notices/subscribers-limit-notice';
import { MailPoet } from 'mailpoet';
import { useParams } from 'react-router-dom';
import { Button } from 'common/button/button';
import { confirmAlert } from 'common/confirm-alert.jsx';
import { Select } from '../../common/form/select/select';
import { BackButton, PageHeader } from '../../common/page-header';
import { TopBarWithBoundary } from '../../common/top-bar/top-bar';

declare global {
  interface Window {
    mailpoet_confirmation_emails?: Array<{ id: number; subject: string }>;
    mailpoet_default_confirmation_email_id?: number;
    mailpoet_pages?: Array<{ id: number; title: string }>;
  }
}

// Mutated in place (not just replaced) so the shared reference stays valid;
// the lists page is a HashRouter SPA and this field remounts per list, so
// module-level state (rather than a value read once) keeps it in sync across
// create/delete actions performed while browsing between lists.
const sharedConfirmationEmails: Array<{ id: number; subject: string }> =
  window.mailpoet_confirmation_emails || [];
const pages = window.mailpoet_pages || [];
const CONFIRMATION_EMAIL_SELECT_ID = 'field_confirmation_email_id';

const confirmationPageValues: Record<string, string> = {
  '0': __('Use global default', 'mailpoet'),
};
pages.forEach((page) => {
  confirmationPageValues[String(page.id)] = page.title;
});

const defaultConfirmationEmailId = String(
  window.mailpoet_default_confirmation_email_id || 0,
);

function ConfirmationEmailField({
  onValueChange,
  item,
}: {
  onValueChange: (e: { target: { name: string; value: string } }) => void;
  item: Record<string, string | number | boolean>;
}) {
  const [emails, setEmails] = useState<Array<{ id: number; subject: string }>>(
    sharedConfirmationEmails,
  );
  const [isCreating, setIsCreating] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const deletingRef = useRef<boolean>(false);
  const savedConfirmationEmailIdRef = useRef<string>('0');

  const selectedId = String(item.confirmation_email_id || '0');

  useEffect(() => {
    if (item.id !== undefined) {
      savedConfirmationEmailIdRef.current = String(
        item.confirmation_email_id || '0',
      );
    }
    // Only capture the saved value when the loaded list changes, not on every edit.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [item.id]);

  const handleCreate = useCallback(async () => {
    setIsCreating(true);
    try {
      const response = await MailPoet.Ajax.post({
        api_version: MailPoet.apiVersion,
        endpoint: 'newsletters',
        action: 'createConfirmationEmail',
      });
      const newEmail = response.data as { id: number; subject: string };
      sharedConfirmationEmails.push(newEmail);
      setEmails([...sharedConfirmationEmails]);
      onValueChange({
        target: { name: 'confirmation_email_id', value: String(newEmail.id) },
      });
      const editUrl = `admin.php?page=mailpoet-newsletter-editor&id=${newEmail.id}`;
      MailPoet.Notice.success(
        `${__(
          'Confirmation email created.',
          'mailpoet',
        )} <a href="${editUrl}" target="_blank" rel="noopener noreferrer">${__(
          'Edit it now',
          'mailpoet',
        )}</a>`,
      );
    } catch (errorResponse) {
      MailPoet.Notice.showApiErrorNotice(errorResponse, {
        scroll: true,
      });
    } finally {
      setIsCreating(false);
    }
  }, [onValueChange]);

  const handleDelete = useCallback(() => {
    if (deletingRef.current) {
      return;
    }
    const email = emails.find((entry) => String(entry.id) === selectedId);
    const subject = email ? email.subject : '';

    confirmAlert({
      title: __('Delete confirmation email', 'mailpoet'),
      message: sprintf(
        // translators: %s is the subject of the confirmation email being deleted.
        __(
          'Are you sure you want to delete “%s”? Lists that use it will go back to the global default confirmation email. This cannot be undone.',
          'mailpoet',
        ),
        subject,
      ),
      confirmLabel: __('Delete', 'mailpoet'),
      returnFocus: () => document.getElementById(CONFIRMATION_EMAIL_SELECT_ID),
      onConfirm: async () => {
        if (deletingRef.current) {
          return;
        }
        deletingRef.current = true;
        setIsDeleting(true);
        try {
          await MailPoet.Ajax.post({
            api_version: MailPoet.apiVersion,
            endpoint: 'newsletters',
            action: 'deleteConfirmationEmail',
            data: { id: selectedId },
          });
          const remainingEmails = sharedConfirmationEmails.filter(
            (entry) => String(entry.id) !== selectedId,
          );
          sharedConfirmationEmails.length = 0;
          sharedConfirmationEmails.push(...remainingEmails);
          setEmails([...sharedConfirmationEmails]);
          const savedId = savedConfirmationEmailIdRef.current;
          const savedIdStillExists = sharedConfirmationEmails.some(
            (entry) => String(entry.id) === savedId,
          );
          const newValue =
            savedId !== selectedId && savedIdStillExists ? savedId : '0';
          onValueChange({
            target: { name: 'confirmation_email_id', value: newValue },
          });
          MailPoet.Notice.success(
            __('Confirmation email deleted.', 'mailpoet'),
          );
        } catch (errorResponse) {
          MailPoet.Notice.showApiErrorNotice(errorResponse, {
            scroll: true,
          });
        } finally {
          deletingRef.current = false;
          setIsDeleting(false);
        }
      },
    });
  }, [emails, selectedId, onValueChange]);

  return (
    <>
      <Select
        name="confirmation_email_id"
        id={CONFIRMATION_EMAIL_SELECT_ID}
        value={selectedId}
        onChange={onValueChange}
        disabled={isDeleting}
      >
        <option value="0">{__('Use global default', 'mailpoet')}</option>
        {emails.map((email) => (
          <option key={email.id} value={String(email.id)}>
            {String(email.id) === defaultConfirmationEmailId
              ? sprintf(
                  // translators: %s is the subject of the global default confirmation email.
                  __('%s (global default)', 'mailpoet'),
                  email.subject,
                )
              : email.subject}
          </option>
        ))}
      </Select>
      <div className="mailpoet-gap" />
      <Button
        type="button"
        variant="secondary"
        dimension="small"
        onClick={handleCreate}
        isDisabled={isCreating || isDeleting}
      >
        {isCreating
          ? __('Creating…', 'mailpoet')
          : __('Create new', 'mailpoet')}
      </Button>
      {selectedId !== '0' && (
        <Button
          variant="secondary"
          dimension="small"
          href={`admin.php?page=mailpoet-newsletter-editor&id=${selectedId}`}
          target="_blank"
          rel="noopener noreferrer"
          isDisabled={isDeleting}
        >
          {__('Edit', 'mailpoet')}
        </Button>
      )}
      {selectedId !== '0' && selectedId !== defaultConfirmationEmailId && (
        <Button
          type="button"
          variant="destructive"
          dimension="small"
          automationId="delete_confirmation_email"
          onClick={handleDelete}
          isDisabled={isDeleting}
        >
          {__('Delete', 'mailpoet')}
        </Button>
      )}
    </>
  );
}

const fields = [
  {
    name: 'name',
    label: MailPoet.I18n.t('segmentFormName'),
    type: 'text',
    tip: MailPoet.I18n.t('segmentFormNameTip'),
  },
  {
    name: 'show_in_manage_subscription_page',
    label: MailPoet.I18n.t('showInManageSubscriptionPage'),
    type: 'checkbox',
    values: {
      show_in_manage_subscription_page: MailPoet.I18n.t(
        'showInManageSubscriptionPageTip',
      ),
    },
    isChecked: true,
  },
  {
    name: 'public_description',
    label: MailPoet.I18n.t('publicDescription'),
    type: 'textarea',
    tip: MailPoet.I18n.t('publicDescriptionTip'),
  },
  {
    name: 'description',
    label: MailPoet.I18n.t('description'),
    type: 'textarea',
    tip: MailPoet.I18n.t('segmentDescriptionTip'),
  },
  {
    name: 'confirmation_email_id',
    label: __('Confirmation email', 'mailpoet'),
    type: 'reactComponent',
    component: ConfirmationEmailField,
    tip: __(
      'Choose a custom confirmation email for subscribers joining this list. If not set, the global default is used.',
      'mailpoet',
    ),
  },
  {
    name: 'confirmation_page_id',
    label: __('Confirmation page', 'mailpoet'),
    type: 'select',
    values: confirmationPageValues,
    tip: __(
      'Choose a custom confirmation page for subscribers joining this list. If not set, the global default is used.',
      'mailpoet',
    ),
  },
];

const messages = {
  onUpdate: function onUpdate() {
    MailPoet.Notice.success(MailPoet.I18n.t('segmentUpdated'));
  },
  onCreate: function onCreate() {
    MailPoet.Notice.success(MailPoet.I18n.t('segmentAdded'));
    MailPoet.trackEvent('Lists > Add new');
  },
};

function SegmentForm() {
  const params = useParams();

  return (
    <div className="mailpoet-main-container">
      <TopBarWithBoundary hideScreenOptions />

      <PageHeader
        heading={
          params.id
            ? __('Edit list', 'mailpoet')
            : __('Add new list', 'mailpoet')
        }
        headingPrefix={
          <BackButton
            href="#/"
            label={__('Lists', 'mailpoet')}
            aria-label={__('Navigate to the lists page', 'mailpoet')}
          />
        }
      />
      <SubscribersLimitNotice />

      <Form
        endpoint="segments"
        fields={fields}
        params={params}
        messages={messages}
      />
    </div>
  );
}

SegmentForm.displayName = 'SegmentForm';

export { SegmentForm };
