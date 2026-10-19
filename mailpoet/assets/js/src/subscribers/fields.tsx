import { Link } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import { SegmentTags, SubscriberTags } from 'common';
import { Badge } from 'common/listings/newsletter-stats/badge';
import { ListingsEngagementScore } from './listings-engagement-score';
import type { Segment, Subscriber } from './api';
import { SUBSCRIBER_SOURCES, SUBSCRIBER_STATUSES } from './filters';

export type FieldElement = { value: string; label: string };

export type SubscriberFieldOptions = {
  segments: FieldElement[];
  tags: FieldElement[];
  // Status labels from the listing response; they carry the global count.
  statusLabels?: Record<string, string>;
  // Defaults to the site's email tracking setting.
  trackingEnabled?: boolean;
};

type FilterBy = Exclude<Field<Subscriber>['filterBy'], false | undefined>;

const SET_OPERATORS: FilterBy = { operators: ['isAny', 'isNone'] };
// DataViews opens a new filter on the first operator; `between` has range
// presets (last 7 days, month to date…), unlike `on`, which picks a single day.
const DATE_OPERATORS: FilterBy = {
  operators: ['between', 'on', 'before', 'after'],
};

// Mirrors SubscribersRepository::bulkDelete: subscribers linked to a WordPress
// user or WooCommerce customer are never permanently deleted.
function isDeletable(subscriber: Subscriber): boolean {
  return (
    Number(subscriber.wp_user_id) === 0 &&
    Number(subscriber.is_woocommerce_user) === 0
  );
}

export function sourceLabel(source: string): string {
  switch (source) {
    case 'form':
      return __('MailPoet subscription form', 'mailpoet');
    case 'imported':
      return __('Import', 'mailpoet');
    case 'administrator':
      return __('Admin', 'mailpoet');
    case 'api':
      return __('API', 'mailpoet');
    case 'wordpress_user':
      return __('WordPress user sync', 'mailpoet');
    case 'wordpress_user_deleted':
      return __('WordPress user (deleted)', 'mailpoet');
    case 'woocommerce_user':
      return __('WooCommerce customer sync', 'mailpoet');
    case 'woocommerce_checkout':
      return __('WooCommerce checkout', 'mailpoet');
    default:
      return __('Unknown', 'mailpoet');
  }
}

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
// the listing is mounted, so the lookup index is built once, on first use,
// instead of paying a linear scan per row × per subscription on every render.
let segmentsById: Map<string, Segment> | null = null;

function getSegmentsById(): Map<string, Segment> {
  if (!segmentsById) {
    segmentsById = new Map<string, Segment>(
      (window.mailpoet_segments ?? []).map((segment: Segment) => [
        String(segment.id),
        segment,
      ]),
    );
  }
  return segmentsById;
}

function subscribedSegments(subscriber: Subscriber): Segment[] {
  const segmentIndex = getSegmentsById();
  return subscriber.subscriptions.reduce<Segment[]>(
    (segments, subscription) => {
      if (subscription.status !== 'subscribed') return segments;
      const segment = segmentIndex.get(String(subscription.segment_id));
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
  group: string | undefined,
  options: SubscriberFieldOptions,
): Field<Subscriber>[] {
  const trackingEnabled =
    options.trackingEnabled ??
    MailPoet.trackingConfig?.emailTrackingEnabled ??
    false;

  const statisticsFields: Field<Subscriber>[] = trackingEnabled
    ? [
        {
          id: 'statistics',
          label: __('Score', 'mailpoet'),
          type: 'integer',
          enableSorting: true,
          enableGlobalSearch: false,
          filterBy: { operators: ['greaterThan', 'lessThan', 'between'] },
          getValue: ({ item }) => item.engagement_score,
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
      filterBy: false,
      enableHiding: false,
      render: ({ item }) => (
        <div>
          <span className="mailpoet-listing-title-row">
            <Link
              className="mailpoet-listing-title"
              data-automation-id={`listing_item_${item.id}`}
              to={`/edit/${item.id}`}
              state={{ backUrl: getBackUrl() }}
            >
              {item.email}
            </Link>
            {group === 'trash' && !isDeletable(item) && (
              <Badge
                type="unknown"
                isInverted={false}
                name={__('Not deletable', 'mailpoet')}
                tooltipId={`not-deletable-${item.id}`}
                tooltip={__(
                  'This subscriber is linked to a WordPress user or WooCommerce customer, so it is kept and will not be permanently deleted.',
                  'mailpoet',
                )}
              />
            )}
          </span>
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
      elements: SUBSCRIBER_STATUSES.map((status) => ({
        value: status,
        label: options.statusLabels?.[status] ?? statusLabel(status),
      })),
      filterBy: SET_OPERATORS,
      render: ({ item }) => <span>{statusLabel(item.status)}</span>,
    },
    {
      id: 'segments',
      label: __('Lists', 'mailpoet'),
      type: 'array',
      enableSorting: false,
      enableGlobalSearch: false,
      elements: options.segments,
      filterBy: SET_OPERATORS,
      getValue: ({ item }) =>
        subscribedSegments(item).map((segment) => String(segment.id)),
      render: ({ item }) => (
        <SegmentTags segments={subscribedSegments(item)} dimension="large" />
      ),
    },
    {
      id: 'tags',
      label: __('Tags', 'mailpoet'),
      type: 'array',
      enableSorting: false,
      enableGlobalSearch: false,
      elements: options.tags,
      filterBy: SET_OPERATORS,
      getValue: ({ item }) => item.tags.map((tag) => String(tag.tag_id)),
      render: ({ item }) => (
        <SubscriberTags
          subscribers={item.tags}
          variant="wordpress"
          isInverted
        />
      ),
    },
    {
      id: 'source',
      label: __('Source', 'mailpoet'),
      type: 'text',
      enableSorting: false,
      enableGlobalSearch: false,
      elements: SUBSCRIBER_SOURCES.map((source) => ({
        value: source,
        label: sourceLabel(source),
      })),
      filterBy: SET_OPERATORS,
      render: ({ item }) => (
        <span>{item.source ? sourceLabel(item.source) : '–'}</span>
      ),
    },
    ...statisticsFields,
    {
      id: 'last_subscribed_at',
      label: __('Subscribed on', 'mailpoet'),
      type: 'date',
      enableSorting: true,
      enableGlobalSearch: false,
      filterBy: DATE_OPERATORS,
      render: ({ item }) => <span>{dateTime(item.last_subscribed_at)}</span>,
    },
    {
      id: 'created_at',
      label: __('Created on', 'mailpoet'),
      type: 'date',
      enableSorting: true,
      enableGlobalSearch: false,
      filterBy: DATE_OPERATORS,
      render: ({ item }) => <span>{dateTime(item.created_at)}</span>,
    },
  ];
}

export function getSubscriberSortableFieldIds(
  trackingEnabled: boolean = MailPoet.trackingConfig?.emailTrackingEnabled ??
    false,
): string[] {
  return getSubscriberFields(() => '', undefined, {
    segments: [],
    tags: [],
    trackingEnabled,
  })
    .filter((field) => field.enableSorting)
    .map((field) => field.id);
}
