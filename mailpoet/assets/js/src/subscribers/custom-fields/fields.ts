import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { createElement } from 'react';
import type { Field } from '@wordpress/dataviews';
import type { CustomField } from './types';

const FIELD_TYPE_LABELS: Record<string, string> = {
  text: __('Text', 'mailpoet'),
  textarea: __('Textarea', 'mailpoet'),
  radio: __('Radio buttons', 'mailpoet'),
  checkbox: __('Checkbox', 'mailpoet'),
  select: __('Select', 'mailpoet'),
  date: __('Date', 'mailpoet'),
};

export const listFields: Field<CustomField>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: true,
    enableSorting: true,
    filterBy: false,
  },
  {
    id: 'label',
    label: __('Label', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: false,
    enableSorting: false,
    filterBy: false,
    render: ({ item }) => createElement('span', null, item.label || '—'),
  },
  {
    id: 'type',
    label: __('Type', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: false,
    enableSorting: true,
    elements: Object.entries(FIELD_TYPE_LABELS).map(([value, label]) => ({
      value,
      label,
    })),
    filterBy: { operators: ['isAny'] },
    render: ({ item }) =>
      createElement('span', null, FIELD_TYPE_LABELS[item.type] ?? item.type),
  },
  // `required` lives in the serialized params blob, so it cannot be filtered or
  // sorted in SQL; it is a display-only column.
  {
    id: 'required',
    label: __('Required', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: false,
    enableSorting: false,
    filterBy: false,
    render: ({ item }) =>
      createElement(
        'span',
        null,
        item.required ? __('Yes', 'mailpoet') : __('No', 'mailpoet'),
      ),
  },
  {
    id: 'subscribers_count',
    label: __('Subscribers', 'mailpoet'),
    type: 'integer',
    enableGlobalSearch: false,
    enableSorting: true,
    filterBy: false,
    render: ({ item }) =>
      createElement('span', null, item.subscribers_count.toLocaleString()),
  },
  // forms_count and dynamic_segments_count are derived from JSON server-side and
  // cannot be done in SQL, so they are display-only (not sortable, not filterable).
  {
    id: 'forms_count',
    label: __('Forms', 'mailpoet'),
    type: 'integer',
    enableGlobalSearch: false,
    enableSorting: false,
    filterBy: false,
    render: ({ item }) =>
      createElement('span', null, item.forms_count.toLocaleString()),
  },
  {
    id: 'dynamic_segments_count',
    label: __('Segments', 'mailpoet'),
    type: 'integer',
    enableGlobalSearch: false,
    enableSorting: false,
    filterBy: false,
    render: ({ item }) =>
      createElement('span', null, item.dynamic_segments_count.toLocaleString()),
  },
  {
    id: 'created_at',
    label: __('Created', 'mailpoet'),
    type: 'datetime',
    enableGlobalSearch: false,
    enableSorting: true,
    filterBy: false,
    render: ({ item }) =>
      createElement(
        'span',
        null,
        item.created_at ? dateI18n('M j, Y', item.created_at, undefined) : '',
      ),
  },
];
