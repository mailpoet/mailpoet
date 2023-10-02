import { Field, Segment } from 'form/types';
import { __ } from '@wordpress/i18n';
import { withBoundary } from '../../common';
import { FilterSegment } from './filter-segment';
import { RecipientCount } from './recipient-count';

const baseFields: Field[] = [
  {
    name: 'segments',
    label: __('Send to', 'mailpoet'),
    type: 'selection',
    placeholder: __('Choose', 'mailpoet'),
    id: 'mailpoet_segments',
    api_version: window.mailpoet_api_version,
    endpoint: 'segments',
    multiple: true,
    filter: function filter(segment: Segment): boolean {
      return !segment?.deleted_at;
    },
    getLabel: function getLabel(segment: Segment): string {
      return segment.name;
    },
    getCount: function getCount(segment: Segment): string {
      return parseInt(segment.subscribers as string, 10).toLocaleString();
    },
    transformChangedValue: function transformChangedValue(
      segmentIds: string[],
    ): Segment[] {
      const allSegments = (this.getItems() || []) as Segment[];
      return segmentIds.map((id) =>
        allSegments.find((segment) => segment.id === id),
      );
    },
    validation: {
      'data-parsley-required': true,
      'data-parsley-required-message': __('Please select a list', 'mailpoet'),
      'data-parsley-segments-with-subscribers': __(
        'Please select a list with subscribers.',
        'mailpoet',
      ),
    },
  },
  {
    name: 'filter-segment-toggle',
    type: 'reactComponent',
    component: withBoundary(FilterSegment),
  },
];

const recipientCountField: Field = {
  name: 'recipient-count',
  type: 'reactComponent',
  component: withBoundary(RecipientCount),
};

export const SendToField: Field = {
  name: 'send-to',
  label: __('Send to', 'mailpoet'),
  tip: __(
    'Subscribers in multiple lists will only receive one email.',
    'mailpoet',
  ),
  fields: baseFields,
};

export const SendToFieldWithCount: Field = {
  name: 'send-to',
  label: __('Send to', 'mailpoet'),
  tip: __(
    'Subscribers in multiple lists will only receive one email.',
    'mailpoet',
  ),
  fields: [...baseFields, recipientCountField],
};
