import { __, _x } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Fragment, useCallback, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';
import type { Action, Field } from '@wordpress/dataviews';

import { MailPoet } from 'mailpoet';
import { Toggle } from 'common/form/toggle/toggle';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { ScheduledIcon } from 'common/listings/newsletter-status';
import { withBoundary } from 'common';
import { NewsletterTypes } from 'newsletters/types';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import { addStatsCTAAction, confirmEdit } from 'newsletters/listings/utils.jsx';
import type { NewsLetter } from 'common/newsletter';
import {
  duplicateNewsletter,
  setNewsletterStatus,
  type NewsletterApiError,
  type NewsletterListingItem,
} from '../api';
import {
  ACTIVATION_NEWSLETTER_GROUPS,
  NewslettersListing,
  type ListingActionHelpers,
} from './newsletters-listing';

// See standard.tsx for the rationale behind widening at the boundary.
function asNewsLetter(item: NewsletterListingItem): NewsLetter {
  return item as unknown as NewsLetter;
}

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

type ReEngagementOptions = {
  afterTimeNumber?: number | string;
  afterTimeType?: 'weeks' | 'months';
};

function renderSettings(item: NewsletterListingItem) {
  if (!item.segments || item.segments.length === 0) {
    return (
      <Link className="mailpoet-listing-error" to={`/send/${item.id}`}>
        {__('You need to select a list to send to.', 'mailpoet')}
      </Link>
    );
  }
  const segments = ReactStringReplace(
    __('Send to %1$s', 'mailpoet'),
    '%1$s',
    (_match: string, i: number) => (
      <Fragment key={i}>
        <SegmentTags
          segments={item.segments as unknown as { id: string; name: string }[]}
          key={`segment-${i}`}
        />
        <FilterSegmentTag
          key={`filter-segment-${i}`}
          newsletter={asNewsLetter(item)}
        />
      </Fragment>
    ),
  );

  const options = (item.options ?? {}) as ReEngagementOptions;
  const number = Number(options.afterTimeNumber ?? 0);
  // Default to months when the option is missing or unexpected; only an
  // explicit 'weeks' value renders week strings.
  const isMonths = options.afterTimeType !== 'weeks';

  let frequency: string = isMonths
    ? _x(
        'month',
        'month in the sentence "1 month after inactivity"',
        'mailpoet',
      )
    : _x('week', 'week in the sentence "1 week after inactivity"', 'mailpoet');
  if (number > 1) {
    frequency = isMonths
      ? _x(
          'months',
          'months in the sentence "5 months after inactivity"',
          'mailpoet',
        )
      : _x(
          'weeks',
          'weeks in the sentence "5 weeks after inactivity"',
          'mailpoet',
        );
  }

  const sendingFrequency = _x(
    '{$count} {$frequency} after inactivity',
    'example: "5 months after inactivity"',
    'mailpoet',
  )
    .replace('{$count}', String(number))
    .replace('{$frequency}', frequency);

  return (
    <span>
      {segments}
      <div className="mailpoet-listing-schedule">
        <div className="mailpoet-listing-schedule-icon">
          <ScheduledIcon />
        </div>
        {sendingFrequency}
      </div>
    </span>
  );
}

type StatusOverrides = Record<string, 'active' | 'draft'>;

function buildFields(
  onToggleStatus: (item: NewsletterListingItem, active: boolean) => void,
  statusOverrides: StatusOverrides,
): Field<NewsletterListingItem>[] {
  return [
    {
      id: 'subject',
      label: __('Subject', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: true,
      render: ({ item }) => (
        <a
          className="mailpoet-listing-title"
          href={MailPoet.getActiveEmailEditorUrl(item)}
          data-automation-id={`listing_item_${item.id}`}
          onClick={(event) => {
            event.preventDefault();
            confirmEdit(item);
          }}
        >
          {item.subject}
        </a>
      ),
    },
    {
      id: 'settings',
      label: __('Settings', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => renderSettings(item),
    },
    ...((mailpoetTrackingEnabled
      ? [
          {
            id: 'statistics',
            label: __('Clicked, Opened', 'mailpoet'),
            enableSorting: false,
            enableGlobalSearch: false,
            render: ({ item }) => {
              const totalSent = Number(item.total_sent ?? 0);
              return (
                <Statistics
                  newsletter={asNewsLetter(item)}
                  isSent={totalSent > 0 && !!item.statistics}
                />
              );
            },
          },
        ]
      : []) as Field<NewsletterListingItem>[]),
    {
      id: 'status',
      label: __('Status', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => {
        const totalSent = Number(item.total_sent ?? 0);
        const totalSentMessage = _x(
          '%1$d sent',
          'number of welcome emails sent',
          'mailpoet',
        ).replace('%1$d', totalSent.toLocaleString());
        const status = statusOverrides[String(item.id)] ?? item.status;
        return (
          <div>
            <Toggle
              className="mailpoet-listing-status-toggle"
              onCheck={(checked: boolean) => {
                onToggleStatus(item, checked);
              }}
              data-id={item.id}
              dimension="small"
              checked={status === 'active'}
            />
            <p className="mailpoet-listing-stats-description">
              <Link
                to={`/sending-status/${item.id}`}
                data-automation-id={`sending_status_${item.id}`}
              >
                {totalSentMessage}
              </Link>
            </p>
          </div>
        );
      },
    },
    {
      id: 'updated_at',
      label: __('Last modified on', 'mailpoet'),
      type: 'datetime',
      enableSorting: true,
      enableGlobalSearch: false,
      render: ({ item }) =>
        item.updated_at ? (
          <>
            {MailPoet.Date.short(item.updated_at)}
            <br />
            {MailPoet.Date.time(item.updated_at)}
          </>
        ) : null,
    },
  ];
}

function NewsletterListReEngagementComponent() {
  // The status toggle is optimistic: it flips immediately, then reverts if the
  // REST call is rejected (subscribers limit, unauthorized sender, …).
  const [statusOverrides, setStatusOverrides] = useState<StatusOverrides>({});
  // Captured from the listing's action helpers so the status toggle can
  // refresh the listing — toggling a status moves the email between the
  // status group tabs, whose counts must update.
  const refreshListingRef = useRef<(() => void) | null>(null);

  const handleToggle = useCallback(
    (item: NewsletterListingItem, active: boolean): void => {
      const id = String(item.id);
      const nextStatus = active ? 'active' : 'draft';
      setStatusOverrides((current) => ({ ...current, [id]: nextStatus }));
      void (async (): Promise<void> => {
        try {
          const response = await setNewsletterStatus(
            Number(item.id),
            nextStatus,
          );
          if (response.data.status === 'active') {
            MailPoet.Notice.success(
              __('Your Re-engagement Email is now activated!', 'mailpoet'),
            );
          }
          refreshListingRef.current?.();
        } catch (error) {
          setStatusOverrides((current) => ({
            ...current,
            [id]: item.status === 'active' ? 'active' : 'draft',
          }));
          MailPoet.Notice.error(
            (error as NewsletterApiError).message ||
              __(
                'Could not update the re-engagement email status.',
                'mailpoet',
              ),
          );
        }
      })();
    },
    [],
  );

  // Drop an optimistic override once the listing reports the same status;
  // keeping it would mask a later server-side change to that email.
  const handleItemsLoaded = useCallback((loaded: NewsletterListingItem[]) => {
    setStatusOverrides((current) => {
      const ids = Object.keys(current);
      if (ids.length === 0) return current;
      const next: StatusOverrides = {};
      ids.forEach((id) => {
        const item = loaded.find((entry) => String(entry.id) === id);
        if (!item || item.status !== current[id]) {
          next[id] = current[id];
        }
      });
      return Object.keys(next).length === ids.length ? current : next;
    });
  }, []);

  const fields = useMemo(
    () => buildFields(handleToggle, statusOverrides),
    [handleToggle, statusOverrides],
  );

  const itemActions = useCallback(
    (helpers: ListingActionHelpers): Action<NewsletterListingItem>[] => {
      refreshListingRef.current = helpers.refresh;
      return addStatsCTAAction(
        [
          {
            id: 'preview',
            label: __('Preview', 'mailpoet'),
            context: 'single',
            supportsBulk: false,
            callback: (targets: NewsletterListingItem[]) => {
              const target = targets[0];
              if (target?.preview_url) {
                window.open(
                  String(target.preview_url),
                  '_blank',
                  'noopener,noreferrer',
                );
              }
            },
          },
          {
            id: 'duplicate',
            label: __('Duplicate', 'mailpoet'),
            context: 'single',
            supportsBulk: false,
            callback: (targets: NewsletterListingItem[]) => {
              const target = targets[0];
              if (!target) return;
              void (async (): Promise<void> => {
                try {
                  const response = await duplicateNewsletter(Number(target.id));
                  MailPoet.Notice.success(
                    __('Email "%1$s" has been duplicated.', 'mailpoet').replace(
                      '%1$s',
                      escapeHTML(response.data.subject),
                    ),
                  );
                  helpers.refresh();
                } catch (error) {
                  MailPoet.Notice.error(
                    (error as NewsletterApiError).message ||
                      __('The action could not be completed.', 'mailpoet'),
                    { scroll: true },
                  );
                }
              })();
            },
          },
          {
            id: 'edit',
            label: __('Edit', 'mailpoet'),
            context: 'single',
            isPrimary: true,
            supportsBulk: false,
            callback: (targets: NewsletterListingItem[]) => {
              if (targets[0]) confirmEdit(targets[0]);
            },
          },
        ],
        helpers.navigate,
      ) as Action<NewsletterListingItem>[];
    },
    [],
  );

  return (
    <NewslettersListing
      type="re_engagement"
      baseUrl="re_engagement"
      fields={fields}
      defaultFields={[
        'settings',
        ...(mailpoetTrackingEnabled ? ['statistics'] : []),
        'status',
        'updated_at',
      ]}
      defaultSort={{ field: 'updated_at', direction: 'desc' }}
      itemActions={itemActions}
      onItemsLoaded={handleItemsLoaded}
      supportedGroups={ACTIVATION_NEWSLETTER_GROUPS}
      emptyState={() => (
        <NewsletterTypes
          filter={(type) => type.slug === 're_engagement'}
          hideScreenOptions={false}
        />
      )}
    />
  );
}

NewsletterListReEngagementComponent.displayName = 'NewsletterListReEngagement';
export const NewsletterListReEngagement = withBoundary(
  NewsletterListReEngagementComponent,
);
