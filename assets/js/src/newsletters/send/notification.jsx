import _ from 'underscore';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import Scheduling from 'newsletters/types/notification/scheduling.jsx';
import SenderField from 'newsletters/send/sender_address_field.jsx';

let fields = [
  {
    name: 'subject',
    label: MailPoet.I18n.t('subjectLine'),
    tip: MailPoet.I18n.t('postNotificationSubjectLineTip'),
    type: 'text',
    validation: {
      'data-parsley-required': true,
      'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError'),
    },
  },
  {
    name: 'options',
    label: MailPoet.I18n.t('selectFrequency'),
    type: 'reactComponent',
    component: Scheduling,
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('segments'),
    tip: MailPoet.I18n.t('segmentsTip'),
    type: 'selection',
    placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
    id: 'mailpoet_segments',
    api_version: window.mailpoet_api_version,
    endpoint: 'segments',
    multiple: true,
    filter: function filter(segment) {
      return !segment.deleted_at;
    },
    getLabel: function getLabel(segment) {
      return `${segment.name} (${parseInt(segment.subscribers, 10).toLocaleString()})`;
    },
    transformChangedValue: function transformChangedValue(segmentIds) {
      const allSegments = this.getItems();
      return _.map(segmentIds, (id) => _.find(allSegments, (segment) => segment.id === id));
    },
    validation: {
      'data-parsley-required': true,
      'data-parsley-required-message': MailPoet.I18n.t('noSegmentsSelectedError'),
    },
  },
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
