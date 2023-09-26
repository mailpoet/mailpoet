import { __ } from '@wordpress/i18n';
import { SendEventConditions } from 'newsletters/automatic-emails/send-event-conditions.jsx';
import { GATrackingField } from 'newsletters/send/ga-tracking';
import { withBoundary } from 'common';

const emails = window.mailpoet_woocommerce_automatic_emails || [];

const getAutomaticEmailFields = (newsletter) => {
  const email = emails[newsletter.options.group];
  if (!email) {
    return false;
  }
  const emailOptions = newsletter.options;
  const fields = [
    {
      name: 'email-header',
      label: null,
      tip: null,
      fields: [
        {
          name: 'subject',
          customLabel: __('Subject', 'mailpoet'),
          className: 'mailpoet-form-field-subject',
          placeholder: __('Type newsletter subject', 'mailpoet'),
          tooltip: __(
            "Be creative! It's the first thing that your subscribers see. Tempt them to open your email.",
            'mailpoet',
          ),
          type: 'text',
          validation: {
            'data-parsley-required': true,
            'data-parsley-required-message': __(
              'Please specify a subject',
              'mailpoet',
            ),
            maxLength: 250,
          },
        },
        {
          name: 'preheader',
          customLabel: __('Preview text', 'mailpoet'),
          className: 'mailpoet-form-field-preheader',
          placeholder: __(
            'Type preview text (usually displayed underneath the subject line in the inbox)',
            'mailpoet',
          ),
          tooltip: `${__(
            "This optional text will appear in your subscribers' inboxes, beside the subject line. Write something enticing!",
            'mailpoet',
          )} ${__(
            'Max length is 250 characters, however, we recommend 80 characters on a single line.',
            'mailpoet',
          )}`,
          type: 'textarea',
          validation: {
            maxLength: 250,
          },
        },
      ],
    },
    {
      name: 'options',
      label: __('Send this %1s Automatic Email when...', 'mailpoet').replace(
        '%1s',
        email.title,
      ),
      type: 'reactComponent',
      component: withBoundary(SendEventConditions),
      email,
      emailOptions,
    },
    GATrackingField,
    {
      name: 'sender',
      label: __('Sender', 'mailpoet'),
      tip: __('Your name and email', 'mailpoet'),
      fields: [
        {
          name: 'sender_name',
          type: 'text',
          placeholder: __('John Doe', 'mailpoet'),
          validation: {
            'data-parsley-required': true,
          },
        },
        {
          name: 'sender_address',
          type: 'text',
          placeholder: __('john.doe@email.com', 'mailpoet'),
          validation: {
            'data-parsley-required': true,
            'data-parsley-type': 'email',
          },
        },
      ],
    },
    {
      name: 'empty',
      type: 'empty',
    },
    {
      name: 'reply-to',
      label: __('Reply-to', 'mailpoet'),
      tip: __(
        'When your subscribers reply to your emails, their emails will go to this address.',
        'mailpoet',
      ),
      inline: true,
      fields: [
        {
          name: 'reply_to_name',
          type: 'text',
          placeholder: __('John Doe', 'mailpoet'),
        },
        {
          name: 'reply_to_address',
          type: 'text',
          placeholder: __('john.doe@email.com', 'mailpoet'),
          validation: {
            'data-parsley-type': 'email',
          },
        },
      ],
    },
  ];

  return fields;
};

export const AutomaticEmailFields = {
  getFields: function getFields(newsletter) {
    return getAutomaticEmailFields(newsletter);
  },
  getSendButtonOptions: function getSendButtonOptions() {
    return {
      value: __('Activate', 'mailpoet'),
    };
  },
};
