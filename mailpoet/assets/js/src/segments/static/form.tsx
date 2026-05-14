import { useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Form } from 'form/form.jsx';
import { SubscribersLimitNotice } from 'notices/subscribers-limit-notice';
import { MailPoet } from 'mailpoet';
import { useParams } from 'react-router-dom';
import { Button } from 'common/button/button';
import { Select } from '../../common/form/select/select';
import { BackButton, PageHeader } from '../../common/page-header';
import { TopBarWithBoundary } from '../../common/top-bar/top-bar';

declare global {
  interface Window {
    mailpoet_confirmation_emails?: Array<{ id: number; subject: string }>;
    mailpoet_pages?: Array<{ id: number; title: string }>;
  }
}

const initialConfirmationEmails = window.mailpoet_confirmation_emails || [];
const pages = window.mailpoet_pages || [];

const confirmationPageValues: Record<string, string> = {
  '0': __('Use global default', 'mailpoet'),
};
pages.forEach((page) => {
  confirmationPageValues[String(page.id)] = page.title;
});

function ConfirmationEmailField({
  onValueChange,
  item,
}: {
  onValueChange: (e: { target: { name: string; value: string } }) => void;
  item: Record<string, string>;
}) {
  const [emails, setEmails] = useState<Array<{ id: number; subject: string }>>(
    initialConfirmationEmails,
  );
  const [isCreating, setIsCreating] = useState(false);

  const selectedId = item.confirmation_email_id || '0';

  const handleCreate = useCallback(async () => {
    setIsCreating(true);
    try {
      const response = await MailPoet.Ajax.post({
        api_version: MailPoet.apiVersion,
        endpoint: 'newsletters',
        action: 'createConfirmationEmail',
      });
      const newEmail = response.data as { id: number; subject: string };
      setEmails((prev) => [...prev, newEmail]);
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

  return (
    <>
      <Select
        name="confirmation_email_id"
        id="field_confirmation_email_id"
        value={selectedId}
        onChange={onValueChange}
      >
        <option value="0">{__('Use global default', 'mailpoet')}</option>
        {emails.map((email) => (
          <option key={email.id} value={String(email.id)}>
            {email.subject}
          </option>
        ))}
      </Select>
      <div className="mailpoet-gap" />
      <Button
        type="button"
        variant="secondary"
        dimension="small"
        onClick={handleCreate}
        isDisabled={isCreating}
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
        >
          {__('Edit', 'mailpoet')}
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
    name: 'description',
    label: MailPoet.I18n.t('description'),
    type: 'textarea',
    tip: MailPoet.I18n.t('segmentDescriptionTip'),
  },
  {
    name: 'public_description',
    label: MailPoet.I18n.t('publicDescription'),
    type: 'textarea',
    tip: MailPoet.I18n.t('publicDescriptionTip'),
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
