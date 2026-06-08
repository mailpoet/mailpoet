import { createElement } from 'react';
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import { ListingsEngagementScore } from 'subscribers/listings-engagement-score';
import type { SegmentListingItem } from './api';

function count(item: SegmentListingItem, status: string): JSX.Element {
  return createElement(
    'span',
    {
      'data-automation-id': `listing_item_${item.id}_${status}_count`,
    },
    Number(item.subscribers_count[status] ?? 0).toLocaleString(),
  );
}

function dateTime(value: string | null): JSX.Element {
  if (!value) return createElement('span', null, '—');
  return createElement(
    'span',
    null,
    createElement('span', null, MailPoet.Date.short(value)),
    createElement('br', null),
    createElement('span', null, MailPoet.Date.time(value)),
  );
}

export const segmentFields: Field<SegmentListingItem>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) => {
      const privateLabel =
        Number(item.show_in_manage_subscription_page) === 0
          ? createElement(
              'span',
              { className: 'mailpoet-listing-title-private-label' },
              ` ${MailPoet.I18n.t('privateListLabel')}`,
            )
          : null;
      const children = [item.name, privateLabel].filter(Boolean) as Array<
        string | JSX.Element
      >;
      const props = {
        className: 'mailpoet-listing-title',
        'data-automation-id': `listing_item_${item.id}`,
      };
      const wrapLegacyNameSelector = (title: JSX.Element): JSX.Element =>
        createElement(
          'span',
          { 'data-automation-id': `segment_name_${item.name}` },
          title,
        );
      if (item.type === 'wp_users' || item.type === 'woocommerce_users') {
        return createElement(
          'div',
          null,
          wrapLegacyNameSelector(createElement('span', props, ...children)),
        );
      }
      return createElement(
        'div',
        null,
        wrapLegacyNameSelector(
          createElement(
            'a',
            { ...props, href: `#/edit/${item.id}` },
            ...children,
          ),
        ),
      );
    },
  },
  {
    id: 'description',
    label: __('Description', 'mailpoet'),
    enableSorting: false,
    enableGlobalSearch: false,
  },
  ...(MailPoet.trackingConfig.emailTrackingEnabled
    ? [
        {
          id: 'average_engagement_score',
          label: MailPoet.I18n.t('listScore'),
          enableSorting: true,
          enableGlobalSearch: false,
          render: ({ item }) =>
            createElement(ListingsEngagementScore, {
              id: Number(item.id),
              engagementScore: item.average_engagement_score,
            }),
        },
      ]
    : []),
  {
    id: 'subscribed',
    label: MailPoet.I18n.t('subscribed'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => count(item, 'subscribed'),
  },
  {
    id: 'unconfirmed',
    label: MailPoet.I18n.t('unconfirmed'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => count(item, 'unconfirmed'),
  },
  {
    id: 'unsubscribed',
    label: MailPoet.I18n.t('unsubscribed'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => count(item, 'unsubscribed'),
  },
  {
    id: 'inactive',
    label: MailPoet.I18n.t('inactive'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => count(item, 'inactive'),
  },
  {
    id: 'bounced',
    label: MailPoet.I18n.t('bounced'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => count(item, 'bounced'),
  },
  {
    id: 'created_at',
    label: MailPoet.I18n.t('createdOn'),
    type: 'datetime',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) => dateTime(item.created_at),
  },
];
