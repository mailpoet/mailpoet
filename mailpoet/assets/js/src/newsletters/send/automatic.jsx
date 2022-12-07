import { MailPoet } from 'mailpoet';
import { SendEventConditions } from 'newsletters/automatic_emails/send_event_conditions.jsx';
import { GATrackingField } from 'newsletters/send/ga_tracking';
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
          customLabel: MailPoet.I18n.t('subjectLabel'),
          className: 'mailpoet-form-field-subject',
          placeholder: MailPoet.I18n.t('subjectLine'),
          tooltip: MailPoet.I18n.t('subjectLineTip'),
          type: 'text',
          validation: {
            'data-parsley-required': true,
            'data-parsley-required-message': MailPoet.I18n.t(
              'emptySubjectLineError',
            ),
            maxLength: 250,
          },
        },
        {
          name: 'preheader',
          customLabel: MailPoet.I18n.t('preheaderLabel'),
          className: 'mailpoet-form-field-preheader',
          placeholder: MailPoet.I18n.t('preheaderLine'),
          tooltip: `${MailPoet.I18n.t('preheaderLineTip1')} ${MailPoet.I18n.t(
            'preheaderLineTip2',
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
      label: MailPoet.I18n.t('sendAutomaticEmailWhenHeading').replace(
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
      label: MailPoet.I18n.t('sender'),
      tip: MailPoet.I18n.t('senderTip'),
      fields: [
        {
          name: 'sender_name',
          type: 'text',
          placeholder: MailPoet.I18n.t('senderNamePlaceholder'),
          validation: {
            'data-parsley-required': true,
          },
        },
        {
          name: 'sender_address',
          type: 'text',
          placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
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
      label: MailPoet.I18n.t('replyTo'),
      tip: MailPoet.I18n.t('replyToTip'),
      inline: true,
      fields: [
        {
          name: 'reply_to_name',
          type: 'text',
          placeholder: MailPoet.I18n.t('replyToNamePlaceholder'),
        },
        {
          name: 'reply_to_address',
          type: 'text',
          placeholder: MailPoet.I18n.t('replyToAddressPlaceholder'),
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
      value: MailPoet.I18n.t('activate'),
    };
  },
};
