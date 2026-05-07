import { __ } from '@wordpress/i18n';
import { createElement } from 'react';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
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
  },
  {
    id: 'label',
    label: __('Label', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: false,
    enableSorting: false,
    render: ({ item }) => createElement('span', null, item.label || '—'),
  },
  {
    id: 'type',
    label: __('Type', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: false,
    enableSorting: true,
    render: ({ item }) =>
      createElement('span', null, FIELD_TYPE_LABELS[item.type] ?? item.type),
  },
  {
    id: 'subscribers_count',
    label: __('Subscribers', 'mailpoet'),
    type: 'integer',
    enableGlobalSearch: false,
    enableSorting: true,
    render: ({ item }) =>
      createElement('span', null, item.subscribers_count.toLocaleString()),
  },
  {
    id: 'forms_count',
    label: __('Forms', 'mailpoet'),
    type: 'integer',
    enableGlobalSearch: false,
    enableSorting: false,
    render: ({ item }) =>
      createElement('span', null, item.forms_count.toLocaleString()),
  },
  {
    id: 'dynamic_segments_count',
    label: __('Segments', 'mailpoet'),
    type: 'integer',
    enableGlobalSearch: false,
    enableSorting: false,
    render: ({ item }) =>
      createElement('span', null, item.dynamic_segments_count.toLocaleString()),
  },
  {
    id: 'created_at',
    label: __('Created', 'mailpoet'),
    type: 'datetime',
    enableGlobalSearch: false,
    enableSorting: true,
    render: ({ item }) =>
      createElement(
        'span',
        null,
        item.created_at ? MailPoet.Date.short(item.created_at) : '',
      ),
  },
];
