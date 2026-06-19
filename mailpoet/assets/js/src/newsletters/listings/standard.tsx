import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { Action, Field } from '@wordpress/dataviews';

import { MailPoet } from 'mailpoet';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { ErrorBoundary, withBoundary } from 'common';
import type { NewsLetter } from 'common/newsletter';
import { NewsletterTypes } from 'newsletters/types';
import { QueueStatus } from 'newsletters/listings/queue-status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import { addStatsCTAAction, confirmEdit } from 'newsletters/listings/utils.jsx';
import {
  duplicateNewsletter,
  onNewslettersListingExtras,
  type NewsletterApiError,
  type NewsletterListingItem,
} from '../api';
import {
  NewsletterShareModal,
  SHARE_VISIBILITY_PRIVATE,
  SHARE_VISIBILITY_PUBLIC,
} from './share-modal';
import {
  STANDARD_NEWSLETTER_GROUPS,
  NewslettersListing,
  toNewsletterMailerLog,
  type ListingActionHelpers,
  type NewsletterMailerLog,
} from './newsletters-listing';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

function isDetailedAnalyticsRestricted(): boolean {
  const capability = MailPoet.capabilities?.detailedAnalytics;
  return !capability || capability.isRestricted;
}

// Several shared components (QueueStatus, Statistics, NewsletterShareModal,
// FilterSegmentTag, …) accept the legacy `NewsLetter` shape which inventories
// every newsletter field including ones the listing response does not carry
// (body, created_at, ga_campaign, …). The listing-rendering code paths never
// read those fields, so we widen at the boundary instead of re-typing every
// consumer.
function asNewsLetter(item: NewsletterListingItem): NewsLetter {
  return item as unknown as NewsLetter;
}

function buildFields(
  currentTime?: string,
  mailerLog: NewsletterMailerLog = { status: '' },
): Field<NewsletterListingItem>[] {
  return [
    {
      // `name` (not `subject`) so the repository's `applySorting` composes the
      // sort key from `CONCAT(post_title, subject)` — matching the value
      // rendered below (campaign name + subject) for post/campaign emails.
      id: 'name',
      label: __('Subject', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: true,
      getValue: ({ item }) => item.campaign_name || item.subject,
      render: ({ item }) => {
        const subject =
          (item.queue &&
            (item.queue as { newsletter_rendered_subject?: string })
              .newsletter_rendered_subject) ||
          item.subject;
        return (
          <a
            className="mailpoet-listing-title"
            href="#"
            data-automation-id={`listing_item_${item.id}`}
            onClick={(event) => {
              event.preventDefault();
              confirmEdit(item);
            }}
          >
            {item.campaign_name ? (
              <>
                {item.campaign_name}
                <br />
                <span className="mailpoet-listing-subtitle">{subject}</span>
              </>
            ) : (
              subject
            )}
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
        <ErrorBoundary>
          <SegmentTags
            segments={
              item.segments as unknown as { id: string; name: string }[]
            }
            dimension="large"
          />
          <FilterSegmentTag newsletter={asNewsLetter(item)} dimension="large" />
        </ErrorBoundary>
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

function NewsletterListStandardComponent() {
  const [shareNewsletter, setShareNewsletter] =
    useState<NewsletterListingItem | null>(null);
  const refreshListingRef = useRef<(() => void) | null>(null);

  const openShareModal = useCallback((item: NewsletterListingItem) => {
    setShareNewsletter(item);
    MailPoet.trackEvent('Emails > Share modal opened');
  }, []);

  const closeShareModal = useCallback(() => setShareNewsletter(null), []);

  const makeNewsletterPublic = useCallback(
    async (item: NewsletterListingItem) => {
      try {
        // `updateShareVisibility` still lives on the legacy JSON endpoint.
        const response = await MailPoet.Ajax.post<{
          data: NewsletterListingItem;
        }>({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'updateShareVisibility',
          data: {
            id: item.id,
            share_visibility: SHARE_VISIBILITY_PUBLIC,
          },
        });
        setShareNewsletter(response.data);
        refreshListingRef.current?.();
        MailPoet.trackEvent('Emails > Share email made public');
      } catch (response) {
        const apiResponse = response as { errors?: { message?: string }[] };
        if (apiResponse.errors && apiResponse.errors.length > 0) {
          MailPoet.Notice.showApiErrorNotice(apiResponse, { scroll: true });
        }
      }
    },
    [],
  );

  const makeNewsletterPrivate = useCallback(
    async (item: NewsletterListingItem, refresh: () => void) => {
      try {
        await MailPoet.Ajax.post<{ data: NewsletterListingItem }>({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'updateShareVisibility',
          data: {
            id: item.id,
            share_visibility: SHARE_VISIBILITY_PRIVATE,
          },
        });
        refresh();
        MailPoet.Notice.success(
          __('Email "%1$s" is now private.', 'mailpoet').replace(
            '%1$s',
            escapeHTML(item.campaign_name || item.subject),
          ),
        );
        MailPoet.trackEvent('Emails > Share email made private');
      } catch (response) {
        const apiResponse = response as { errors?: { message?: string }[] };
        if (apiResponse.errors && apiResponse.errors.length > 0) {
          MailPoet.Notice.showApiErrorNotice(apiResponse, { scroll: true });
        }
      }
    },
    [],
  );

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

  const itemActions = useCallback(
    (helpers: ListingActionHelpers): Action<NewsletterListingItem>[] =>
      // `addStatsCTAAction` is still in untyped jsx; cast back to the typed
      // DataViews `Action` shape so consumers don't see `any[]`.
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
          {
            id: 'share',
            label: __('Share', 'mailpoet'),
            context: 'single',
            isPrimary: true,
            supportsBulk: false,
            // Only sent emails can be shared. The modal still offers
            // "make public" when a sent email is supported but private.
            isEligible: (item: NewsletterListingItem) =>
              Boolean(item.is_share_supported),
            callback: (targets: NewsletterListingItem[]) => {
              if (targets[0]) {
                refreshListingRef.current = helpers.refresh;
                openShareModal(targets[0]);
              }
            },
          },
          {
            id: 'make-private',
            label: __('Make private', 'mailpoet'),
            context: 'single',
            supportsBulk: false,
            isEligible: (item: NewsletterListingItem) =>
              Boolean(item.can_share) &&
              item.effective_share_visibility === SHARE_VISIBILITY_PUBLIC,
            callback: (targets: NewsletterListingItem[]) => {
              if (targets[0]) {
                void makeNewsletterPrivate(targets[0], helpers.refresh);
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
                  const apiError = error as NewsletterApiError;
                  MailPoet.Notice.error(
                    apiError.message ||
                      __('The action could not be completed.', 'mailpoet'),
                    { scroll: true },
                  );
                }
              })();
            },
          },
        ],
        helpers.navigate,
      ) as Action<NewsletterListingItem>[],
    [makeNewsletterPrivate, openShareModal],
  );

  const trackingHooksOk =
    mailpoetTrackingEnabled && !isDetailedAnalyticsRestricted();

  return (
    <>
      <NewslettersListing
        type="standard"
        baseUrl="standard"
        fields={fields}
        defaultFields={[
          'status',
          'segments',
          ...(mailpoetTrackingEnabled ? ['statistics'] : []),
          'sent_at',
        ]}
        defaultSort={{ field: 'sent_at', direction: 'desc' }}
        itemActions={itemActions}
        supportsExportStats={trackingHooksOk}
        supportedGroups={STANDARD_NEWSLETTER_GROUPS}
        emptyState={() => (
          <NewsletterTypes
            filter={(type) => type.slug === 'standard'}
            hideScreenOptions={false}
          />
        )}
      />
      {shareNewsletter && (
        <NewsletterShareModal
          newsletter={asNewsLetter(shareNewsletter)}
          onClose={closeShareModal}
          onMakePublic={() => {
            void makeNewsletterPublic(shareNewsletter);
          }}
        />
      )}
    </>
  );
}

NewsletterListStandardComponent.displayName = 'NewsletterListStandard';
export const NewsletterListStandard = withBoundary(
  NewsletterListStandardComponent,
);
