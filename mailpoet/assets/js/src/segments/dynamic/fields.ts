import { createElement } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import * as ROUTES from 'segments/routes';
import type { DynamicSegmentListingItem } from './api';

function dateTime(
  value: string | null | undefined,
  segmentId?: number,
): JSX.Element {
  if (!value) return createElement('span', null, '—');
  return createElement(
    'span',
    {
      'data-automation-id': segmentId
        ? `mailpoet_dynamic_segment_updated_at_${segmentId}`
        : 'mailpoet_dynamic_segment_updated_at',
    },
    MailPoet.Date.full(value),
  );
}

function editUnavailableMessage(item: DynamicSegmentListingItem): string {
  return sprintf(
    __('Edit unavailable: %s', 'mailpoet'),
    item.missing_plugin_message?.message ??
      __('Required plugin is inactive.', 'mailpoet'),
  );
}

export const dynamicSegmentFields: Field<DynamicSegmentListingItem>[] = [
  {
    id: 'name',
    label: __('Segment', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableGlobalSearch: true,
    render: ({ item }) =>
      createElement(
        'div',
        {
          'data-automation-id': `mailpoet_dynamic_segment_name_${item.id}`,
        },
        item.is_plugin_missing
          ? createElement('span', null, item.name)
          : createElement(
              'a',
              { href: `#${ROUTES.EDIT_DYNAMIC_SEGMENT}/${item.id}` },
              item.name,
            ),
        item.description ? createElement('div', null, item.description) : null,
        item.is_plugin_missing
          ? createElement(
              'div',
              {
                className: 'mailpoet-segments-dataviews__row-note',
                'data-automation-id': `mailpoet_dynamic_segment_plugin_missing_message_${item.id}`,
              },
              editUnavailableMessage(item),
            )
          : null,
      ),
  },
  {
    id: 'subscribers',
    label: __('Number of subscribers', 'mailpoet'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) =>
      item.is_plugin_missing
        ? createElement('span', null, '—')
        : createElement(
            'div',
            {
              'data-automation-id': `mailpoet_dynamic_segment_count_all_${item.id}`,
            },
            item.count_all,
          ),
  },
  {
    id: 'subscribed',
    label: __('Subscribed', 'mailpoet'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => {
      if (item.is_plugin_missing) return null;
      if (item.count_subscribed === '0') {
        return createElement('span', null, item.count_subscribed);
      }
      return createElement(
        'a',
        {
          'data-automation-id': `mailpoet_dynamic_segment_count_subscribed_${item.id}`,
          className:
            'components-button is-link mailpoet-listing-text-right-align',
          href: item.subscribers_url,
        },
        item.count_subscribed,
      );
    },
  },
  {
    id: 'updated_at',
    label: __('Modified', 'mailpoet'),
    type: 'datetime',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) => dateTime(item.updated_at, item.id),
  },
];
