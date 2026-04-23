import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { createElement } from 'react';
import type { Field } from '@wordpress/dataviews';
import type { Tag } from './types';
import { getSubscribersListingUrl } from './api';

export const listFields: Field<Tag>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: true,
    enableSorting: true,
  },
  {
    id: 'description',
    label: __('Description', 'mailpoet'),
    type: 'text',
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => createElement('span', null, item.description || '—'),
  },
  {
    id: 'subscribers_count',
    label: __('Subscribers', 'mailpoet'),
    type: 'integer',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) =>
      createElement(
        'a',
        {
          href: getSubscribersListingUrl(item.id),
        },
        item.subscribers_count.toLocaleString(),
      ),
  },
  {
    id: 'created_at',
    label: __('Created', 'mailpoet'),
    type: 'datetime',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) =>
      createElement(
        'span',
        null,
        item.created_at ? dateI18n('M j, Y', item.created_at, undefined) : '',
      ),
  },
];

export const formFields: Field<Tag>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    isValid: { required: true, minLength: 1 },
  },
  {
    id: 'description',
    label: __('Description', 'mailpoet'),
    type: 'text',
    Edit: { control: 'textarea', rows: 3 },
  },
];
