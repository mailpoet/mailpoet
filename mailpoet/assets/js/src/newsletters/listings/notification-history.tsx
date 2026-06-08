import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import { useEffect, useMemo, useState } from 'react';
import type { Action, Field } from '@wordpress/dataviews';

import { MailPoet } from 'mailpoet';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { withBoundary } from 'common';
import { QueueStatus } from 'newsletters/listings/queue-status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import { addStatsCTAAction } from 'newsletters/listings/utils.jsx';
import type { NewsLetter } from 'common/newsletter';
import { onNewslettersListingExtras, type NewsletterListingItem } from '../api';
import {
  NOTIFICATION_HISTORY_GROUPS,
  NewslettersListing,
  toNewsletterMailerLog,
  type ListingActionHelpers,
  type NewsletterMailerLog,
} from './newsletters-listing';

// See standard.tsx for the rationale behind widening at the boundary.
function asNewsLetter(item: NewsletterListingItem): NewsLetter {
  return item as unknown as NewsLetter;
}

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

function buildFields(
  currentTime?: string,
  mailerLog: NewsletterMailerLog = { status: '' },
): Field<NewsletterListingItem>[] {
  return [
    {
      id: 'subject',
      label: __('Subject', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: true,
      render: ({ item }) => {
        const subject =
          (item.queue &&
            (item.queue as { newsletter_rendered_subject?: string })
              .newsletter_rendered_subject) ||
          item.subject;
        return (
          <a
            className="mailpoet-listing-title"
            href={item.preview_url}
            target="_blank"
            rel="noopener noreferrer"
            data-automation-id={`listing_item_${item.id}`}
          >
            {subject}
          </a>
        );
      },
    },
    {
      id: 'status',
      label: __('Status', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => (
        <QueueStatus newsletter={asNewsLetter(item)} mailerLog={mailerLog} />
      ),
    },
    {
      id: 'segments',
      label: __('Lists', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => (
        <>
          <SegmentTags
            segments={
              item.segments as unknown as { id: string; name: string }[]
            }
            dimension="large"
          />
          <FilterSegmentTag newsletter={asNewsLetter(item)} dimension="large" />
        </>
      ),
    },
    ...((mailpoetTrackingEnabled
      ? [
          {
            id: 'statistics',
            label: __('Clicked, Opened', 'mailpoet'),
            enableSorting: false,
            enableGlobalSearch: false,
            render: ({ item }) => (
              <Statistics
                newsletter={asNewsLetter(item)}
                currentTime={currentTime}
              />
            ),
          },
        ]
      : []) as Field<NewsletterListingItem>[]),
    {
      id: 'sent_at',
      label: __('Sent on', 'mailpoet'),
      type: 'datetime',
      enableSorting: true,
      enableGlobalSearch: false,
      render: ({ item }) =>
        item.sent_at ? (
          <>
            {MailPoet.Date.short(item.sent_at)}
            <br />
            {MailPoet.Date.time(item.sent_at)}
          </>
        ) : null,
    },
  ];
}

function NewsletterListNotificationHistoryComponent({
  parentId,
}: {
  parentId: string;
}) {
  // `current_time` rides along with the listing response; the Statistics
  // column uses it to flag emails sent in the last few hours.
  const [currentTime, setCurrentTime] = useState<string | undefined>(undefined);
  const [mailerLog, setMailerLog] = useState<NewsletterMailerLog>({
    status: '',
  });
  useEffect(
    () =>
      onNewslettersListingExtras((extras) => {
        setCurrentTime(extras.current_time || undefined);
        setMailerLog(toNewsletterMailerLog(extras.mta_log));
      }),
    [],
  );

  const fields = useMemo(
    () => buildFields(currentTime, mailerLog),
    [currentTime, mailerLog],
  );
  const itemActions = useMemo(
    () =>
      (helpers: ListingActionHelpers): Action<NewsletterListingItem>[] =>
        addStatsCTAAction(
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
          ],
          helpers.navigate,
        ) as Action<NewsletterListingItem>[],
    [],
  );
  return (
    <>
      <p>
        <Link
          className="mailpoet-button button button-secondary button-small"
          to="/notification"
        >
          {__('Back to Post notifications', 'mailpoet')}
        </Link>
      </p>
      <NewslettersListing
        type="notification_history"
        baseUrl={`notification/history/${parentId}`}
        parentId={Number(parentId)}
        fields={fields}
        defaultFields={[
          'status',
          'segments',
          ...(mailpoetTrackingEnabled ? ['statistics'] : []),
          'sent_at',
        ]}
        defaultSort={{ field: 'sent_at', direction: 'desc' }}
        itemActions={itemActions}
        supportedGroups={NOTIFICATION_HISTORY_GROUPS}
      />
    </>
  );
}

NewsletterListNotificationHistoryComponent.displayName =
  'NewsletterListNotificationHistory';
export const NewsletterListNotificationHistory = withBoundary(
  NewsletterListNotificationHistoryComponent,
);
