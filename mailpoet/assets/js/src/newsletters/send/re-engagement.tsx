import { assoc, find, map } from 'lodash/fp';

import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Scheduling } from '../types/re-engagement/scheduling';
import { GATrackingField } from './ga-tracking';
import { SenderField } from './sender-address-field';

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
        // ignore for now until the MailPoet object is refactored to typescript
        // eslint-disable-next-line @typescript-eslint/restrict-template-expressions
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
    type: 'reactComponent',
    component: FormReEngagementScheduling,
  },
  {
    name: 'segments',
    label: __('Lists', 'mailpoet'),
    tip: __(
      'Subscribers in multiple lists will only receive one email.',
      'mailpoet',
    ),
    type: 'selection',
    placeholder: __('Select a list', 'mailpoet'),
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
      'data-parsley-required-message': __('Please select a list', 'mailpoet'),
    },
  },
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
        component: SenderField,
        placeholder: __('john.doe@email.com', 'mailpoet'),
        validation: {
          'data-parsley-required': true,
          'data-parsley-type': 'email',
        },
      },
    ],
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
      value: __('Activate', 'mailpoet'),
    };
  },
};
