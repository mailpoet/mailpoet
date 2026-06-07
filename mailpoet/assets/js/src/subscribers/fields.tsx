import { Link } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import { SegmentTags, SubscriberTags } from 'common';
import { ListingsEngagementScore } from './listings-engagement-score';
import type { Segment, Subscriber } from './api';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

function statusLabel(status: string): string {
  switch (status) {
    case 'subscribed':
      return __('Subscribed', 'mailpoet');
    case 'unconfirmed':
      return __('Unconfirmed', 'mailpoet');
    case 'unsubscribed':
      return __('Unsubscribed', 'mailpoet');
    case 'inactive':
      return __('Inactive', 'mailpoet');
    case 'bounced':
      return __('Bounced', 'mailpoet');
    default:
      return 'Invalid';
  }
}

// `window.mailpoet_segments` is page-bootstrap data that does not change while
// the listing is mounted, so we can build the lookup index once at module load
// instead of paying a linear scan per row × per subscription on every render.
const segmentsById = new Map<string, Segment>(
  (window.mailpoet_segments ?? []).map((segment: Segment) => [
    String(segment.id),
    segment,
  ]),
);

function subscribedSegments(subscriber: Subscriber): Segment[] {
  return subscriber.subscriptions.reduce<Segment[]>(
    (segments, subscription) => {
      if (subscription.status !== 'subscribed') return segments;
      const segment = segmentsById.get(String(subscription.segment_id));
      if (segment) segments.push(segment);
      return segments;
    },
    [],
  );
}

function dateTime(value: string | null): JSX.Element | null {
  if (!value) return null;
  return (
    <>
      {MailPoet.Date.short(value)}
      <br />
      {MailPoet.Date.time(value)}
    </>
  );
}

export function getSubscriberFields(
  getBackUrl: () => string,
): Field<Subscriber>[] {
  const statisticsFields: Field<Subscriber>[] = mailpoetTrackingEnabled
    ? [
        {
          id: 'statistics',
          label: __('Score', 'mailpoet'),
          enableSorting: false,
          enableGlobalSearch: false,
          render: ({ item }) => (
            <div className="mailpoet-listing-stats">
              <Link
                to={`/stats/${String(item.id)}`}
                state={{ backUrl: getBackUrl() }}
              >
                <ListingsEngagementScore
                  id={Number(item.id)}
                  engagementScore={item.engagement_score}
                  engagementScoreType={item.engagement_score_type}
                />
              </Link>
            </div>
          ),
        },
      ]
    : [];

  return [
    {
      id: 'email',
      label: __('Subscriber', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: true,
      render: ({ item }) => (
        <div>
          <Link
            className="mailpoet-listing-title"
            data-automation-id={`listing_item_${item.id}`}
            to={`/edit/${item.id}`}
            state={{ backUrl: getBackUrl() }}
          >
            {item.email}
          </Link>
          <div className="mailpoet-listing-subtitle">
            {item.first_name} {item.last_name}
          </div>
        </div>
      ),
    },
    {
      id: 'status',
      label: __('Status', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: false,
      render: ({ item }) => <span>{statusLabel(item.status)}</span>,
    },
    {
      id: 'segments',
      label: __('Lists', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => (
        <SegmentTags segments={subscribedSegments(item)} dimension="large" />
      ),
    },
    {
      id: 'tags',
      label: __('Tags', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => (
        <SubscriberTags
          subscribers={item.tags}
          variant="wordpress"
          isInverted
        />
      ),
    },
    ...statisticsFields,
    {
      id: 'last_subscribed_at',
      label: __('Subscribed on', 'mailpoet'),
      type: 'datetime',
      enableSorting: true,
      enableGlobalSearch: false,
      render: ({ item }) => <span>{dateTime(item.last_subscribed_at)}</span>,
    },
    {
      id: 'created_at',
      label: __('Created on', 'mailpoet'),
      type: 'datetime',
      enableSorting: true,
      enableGlobalSearch: false,
      render: ({ item }) => <span>{dateTime(item.created_at)}</span>,
    },
  ];
}
