import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { WelcomeScheduling } from 'newsletters/types/welcome/scheduling.jsx';
import { SenderField } from 'newsletters/send/sender_address_field.jsx';
import { GATrackingField } from 'newsletters/send/ga_tracking';
import { withBoundary } from 'common';

let fields = [
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
    label: __('When to send this welcome email?', 'mailpoet'),
    type: 'reactComponent',
    component: withBoundary(WelcomeScheduling),
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
        type: 'reactComponent',
        component: withBoundary(SenderField),
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

fields = Hooks.applyFilters('mailpoet_newsletters_3rd_step_fields', fields);

export const WelcomeNewsletterFields = {
  getFields: function getFields() {
    return fields;
  },
  getSendButtonOptions: function getSendButtonOptions() {
    return {
      value: __('Activate', 'mailpoet'),
    };
  },
};
