import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import Scheduling from 'newsletters/types/welcome/scheduling.jsx';
import SenderField from 'newsletters/send/sender_address_field.jsx';
import GATrackingField from 'newsletters/send/ga_tracking.jsx';

let fields = [
  {
    name: 'email-header',
    label: null,
    tip: null,
    fields: [
      {
        name: 'subject',
        placeholder: MailPoet.I18n.t('subjectLine'),
        tip: MailPoet.I18n.t('subjectLineTip'),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError'),
        },
      },
      {
        name: 'preheader',
        placeholder: MailPoet.I18n.t('preheaderLine'),
        tip: `${MailPoet.I18n.t('preheaderLineTip1')} ${MailPoet.I18n.t('preheaderLineTip2')}`,
        type: 'text',
        validation: {
          'data-parsley-maxlength': 250,
        },
      },
    ],
  },
  {
    name: 'options',
    label: MailPoet.I18n.t('sendWelcomeEmailWhen'),
    type: 'reactComponent',
    component: Scheduling,
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
        type: 'reactComponent',
        component: SenderField,
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

fields = Hooks.applyFilters('mailpoet_newsletters_3rd_step_fields', fields);

export default {
  getFields: function getFields() {
    return fields;
  },
  getSendButtonOptions: function getSendButtonOptions() {
    return {
      value: MailPoet.I18n.t('activate'),
    };
  },
};
