import { assoc, find, map } from 'lodash/fp';

import { MailPoet } from 'mailpoet';
import { Scheduling } from '../types/re_engagement/scheduling';
import { GATrackingField } from './ga_tracking';
import { SenderField } from './sender_address_field';

interface OnValueChangeParam {
  target: {
    name: string;
    value: {
      afterTimeNumber: number | string;
      afterTimeType: string;
    };
  };
}

interface Props {
  item: {
    options: {
      afterTimeNumber: number | string;
      afterTimeType: string;
    };
  };
  onValueChange: (val: OnValueChangeParam) => void;
}

function FormReEngagementScheduling(props: Props): JSX.Element {
  return (
    <Scheduling
      afterTimeNumber={props.item.options.afterTimeNumber.toString()}
      afterTimeType={props.item.options.afterTimeType}
      inactiveSubscribersPeriod={Number(
        MailPoet.deactivateSubscriberAfterInactiveDays,
      )}
      updateAfterTimeNumber={(value) => {
        props.onValueChange({
          target: {
            name: 'options',
            value: assoc('afterTimeNumber', value, props.item.options),
          },
        });
      }}
      updateAfterTimeType={(value) => {
        props.onValueChange({
          target: {
            name: 'options',
            value: assoc('afterTimeType', value, props.item.options),
          },
        });
      }}
    />
  );
}

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
        // ignore for now until the MailPoet object is refactored to typescript
        // eslint-disable-next-line @typescript-eslint/restrict-template-expressions
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
    type: 'reactComponent',
    component: FormReEngagementScheduling,
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('segments'),
    tip: MailPoet.I18n.t('segmentsTip'),
    type: 'selection',
    placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
    id: 'mailpoet_segments',
    api_version: MailPoet.apiVersion,
    endpoint: 'segments',
    multiple: true,
    filter: function filter(segment) {
      return !segment.deleted_at && segment.type !== 'dynamic';
    },
    getLabel: function getLabel(segment) {
      return segment.name;
    },
    getCount: function getCount(segment) {
      return parseInt(segment.subscribers as string, 10).toLocaleString();
    },
    transformChangedValue: function transformChangedValue(segmentIds) {
      const allSegments = this.getItems();
      return map(
        (id) => find((segment) => segment.id === id, allSegments),
        segmentIds,
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
  GATrackingField,
];

export const ReEngagementNewsletterFields = {
  // ignore for now until we refactor the forms to typescript
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  getFields() {
    return fields;
  },
  getSendButtonOptions(): { value: string } {
    return {
      value: MailPoet.I18n.t('activate'),
    };
  },
};
