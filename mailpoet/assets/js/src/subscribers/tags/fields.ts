import { __, sprintf } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { createElement } from 'react';
import type { Field } from '@wordpress/dataviews';
import type { SubscriberCountBucket, Tag } from './types';
import { getSubscribersListingUrl } from './api';

const baseListFields: Field<Tag>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: true,
    enableSorting: true,
    filterBy: false,
  },
  {
    id: 'description',
    label: __('Description', 'mailpoet'),
    type: 'text',
    enableSorting: false,
    enableGlobalSearch: false,
    filterBy: false,
    render: ({ item }) => createElement('span', null, item.description || '—'),
  },
  {
    id: 'subscribers_count',
    label: __('Subscribers', 'mailpoet'),
    type: 'integer',
    enableSorting: true,
    enableGlobalSearch: false,
    filterBy: false,
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
    type: 'date',
    enableSorting: true,
    enableGlobalSearch: false,
    filterBy: { operators: ['on', 'beforeInc', 'afterInc', 'between'] },
    render: ({ item }) =>
      createElement(
        'span',
        null,
        item.created_at ? dateI18n('M j, Y', item.created_at, undefined) : '',
      ),
  },
];

function bucketLabel(bucket: SubscriberCountBucket): string {
  if (bucket.min === 0 && bucket.max === 0) {
    return __('None', 'mailpoet');
  }
  if (bucket.max === null) {
    return sprintf(
      /* translators: %s is a subscriber count, e.g. "10,000+". */
      __('%s+', 'mailpoet'),
      bucket.min.toLocaleString(),
    );
  }
  return `${bucket.min.toLocaleString()}–${bucket.max.toLocaleString()}`;
}

function bucketValueForCount(
  count: number,
  buckets: SubscriberCountBucket[],
): string {
  const match = buckets.find((bucket) =>
    bucket.max === null
      ? count >= bucket.min
      : count >= bucket.min && count <= bucket.max,
  );
  return match ? match.value : '';
}

/**
 * Build the listing fields. The subscriber-count filter is data-driven: its
 * options are the decade buckets the endpoint returns for the current site, so
 * it is only added when at least one tag has subscribers.
 */
export function buildListFields(
  buckets: SubscriberCountBucket[],
): Field<Tag>[] {
  if (buckets.length === 0) {
    return baseListFields;
  }

  return [
    ...baseListFields,
    // Filter-only field (not shown as a column): bridges to the REST
    // `subscribers` filter, mapping a tag's count to its decade bucket.
    {
      id: 'subscribers',
      label: __('Subscribers', 'mailpoet'),
      type: 'text',
      enableSorting: false,
      enableGlobalSearch: false,
      enableHiding: false,
      filterBy: { operators: ['isAny'] },
      elements: buckets.map((bucket) => ({
        value: bucket.value,
        label: bucketLabel(bucket),
      })),
      getValue: ({ item }) =>
        bucketValueForCount(item.subscribers_count, buckets),
    },
  ];
}

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
