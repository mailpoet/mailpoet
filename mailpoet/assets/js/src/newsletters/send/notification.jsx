import _ from 'underscore';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import { NotificationScheduling } from 'newsletters/types/notification/scheduling.jsx';
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
    label: MailPoet.I18n.t('selectFrequency'),
    type: 'reactComponent',
    component: NotificationScheduling,
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
      return segment.name;
    },
    getCount: function getCount(segment) {
      return parseInt(segment.subscribers, 10).toLocaleString();
    },
    transformChangedValue: function transformChangedValue(segmentIds) {
      const allSegments = this.getItems();
      return _.map(segmentIds, (id) =>
        _.find(allSegments, (segment) => segment.id === id),
      );
    },
    validation: {
      'data-parsley-required': true,
      'data-parsley-required-message': MailPoet.I18n.t(
        'noSegmentsSelectedError',
      ),
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
        component: withBoundary(SenderField),
        placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
        validation: {
          'data-parsley-required': true,
          'data-parsley-type': 'email',
        },
      },
    ],
  },
  GATrackingField,
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

export const NotificationNewsletterFields = {
  getFields: function getFields() {
    return fields;
  },
  getSendButtonOptions: function getSendButtonOptions() {
    return {
      value: MailPoet.I18n.t('activate'),
    };
  },
};
