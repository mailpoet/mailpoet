import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Fragment, useCallback, useMemo, useRef, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import { Link } from 'react-router-dom';
import type { Action, Field } from '@wordpress/dataviews';

import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { Toggle } from 'common/form/toggle/toggle';
import { ScheduledIcon } from 'common/listings/newsletter-status';
import { withBoundary } from 'common';
import { NewsletterTypes } from 'newsletters/types';
import {
  monthDayValues,
  nthWeekDayValues,
  timeOfDayValues,
  weekDayValues,
} from 'newsletters/scheduling/common.jsx';
import {
  DEFAULT_DAY,
  formatSelectedValues,
  getDefaultWeekDay,
  getOrderedWeekDayKeys,
} from 'newsletters/scheduling/multi-day';
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
import { confirmEdit } from './utils.jsx';

// See standard.tsx for the rationale behind widening at the boundary.
function asNewsLetter(item: NewsletterListingItem): NewsLetter {
  return item as unknown as NewsLetter;
}

type NotificationOptions = {
  intervalType?: 'daily' | 'weekly' | 'monthly' | 'nthWeekDay' | 'immediately';
  timeOfDay?: string;
  weekDay?: string;
  monthDay?: string;
  nthWeekDay?: string;
};

function renderSettings(item: NewsletterListingItem) {
  if (!item.segments || item.segments.length === 0) {
    return (
      <Link className="mailpoet-listing-error" to={`/send/${item.id}`}>
        {__('You need to select a list to send to.', 'mailpoet')}
      </Link>
    );
  }

  const sendingToSegments = ReactStringReplace(
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

  const options = (item.options ?? {}) as NotificationOptions;
  // `*Values` come from untyped jsx and resolve as `Record<string, any>`; coerce
  // the looked-up values to string at the lookup site so the calling APIs stay
  // safely typed.
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const lookupString = (map: any, key: string | undefined): string => {
    if (!map || key === undefined) return '';
    // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
    const value: unknown = map[key];
    return value !== undefined && value !== null ? String(value) : '';
  };
  const defaultWeekDay = getDefaultWeekDay(MailPoet.wpWeekStartsOn);
  const orderedWeekDayKeys = getOrderedWeekDayKeys(MailPoet.wpWeekStartsOn);
  let frequency = '';
  switch (options.intervalType) {
    case 'daily':
      frequency = __('Daily at %1$s', 'mailpoet').replace(
        '%1$s',
        lookupString(timeOfDayValues, options.timeOfDay),
      );
      break;
    case 'weekly':
      frequency = __('Weekly on %1$s at %2$s', 'mailpoet')
        .replace(
          '%1$s',
          formatSelectedValues(
            options.weekDay ?? '',
            weekDayValues,
            defaultWeekDay,
            orderedWeekDayKeys,
          ),
        )
        .replace('%2$s', lookupString(timeOfDayValues, options.timeOfDay));
      break;
    case 'monthly':
      frequency = __('Monthly on the %1$s at %2$s', 'mailpoet')
        .replace(
          '%1$s',
          formatSelectedValues(
            options.monthDay ?? '',
            monthDayValues as Record<string, string>,
            DEFAULT_DAY,
          ),
        )
        .replace('%2$s', lookupString(timeOfDayValues, options.timeOfDay));
      break;
    case 'nthWeekDay':
      frequency = __('Every %1$s %2$s of the month at %3$s', 'mailpoet')
        .replace('%1$s', lookupString(nthWeekDayValues, options.nthWeekDay))
        .replace('%2$s', lookupString(weekDayValues, options.weekDay))
        .replace('%3$s', lookupString(timeOfDayValues, options.timeOfDay));
      break;
    case 'immediately':
      frequency = __('Immediately', 'mailpoet');
      break;
    default:
      frequency = 'Invalid sending frequency';
  }

  return (
    <span>
      {sendingToSegments}
      <div className="mailpoet-listing-schedule">
        <div className="mailpoet-listing-schedule-icon">
          <ScheduledIcon />
        </div>
        {frequency}
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
    {
      id: 'history',
      label: __('History', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => {
        const childrenCount = Number(item.children_count ?? 0);
        if (childrenCount === 0) {
          return (
            <span className="mailpoet-listing-status-unknown mailpoet-font-extra-small mailpoet-listing-notification-status">
              {__('Not sent yet', 'mailpoet')}
            </span>
          );
        }
        return (
          <Link
            className="mailpoet-nowrap"
            data-automation-id={`history-${item.id}`}
            to={`/notification/history/${item.id}`}
          >
            <Button className="mailpoet-hide-on-mobile" dimension="small">
              {__('View history', 'mailpoet')}
            </Button>
          </Link>
        );
      },
    },
    {
      id: 'status',
      label: __('Status', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => {
        const status = statusOverrides[String(item.id)] ?? item.status;
        return (
          <Toggle
            className="mailpoet-listing-status-toggle"
            onCheck={(checked: boolean) => {
              onToggleStatus(item, checked);
            }}
            data-id={item.id}
            dimension="small"
            checked={status === 'active'}
          />
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

function NewsletterListNotificationComponent() {
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
              __('Your post notification is now active!', 'mailpoet'),
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
              __('Could not update the post notification status.', 'mailpoet'),
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
      return [
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
      ];
    },
    [],
  );

  return (
    <NewslettersListing
      type="notification"
      baseUrl="notification"
      fields={fields}
      defaultFields={['settings', 'history', 'status', 'updated_at']}
      defaultSort={{ field: 'updated_at', direction: 'desc' }}
      itemActions={itemActions}
      onItemsLoaded={handleItemsLoaded}
      supportedGroups={ACTIVATION_NEWSLETTER_GROUPS}
      emptyState={() => (
        <NewsletterTypes
          filter={(type) => type.slug === 'notification'}
          hideScreenOptions={false}
        />
      )}
    />
  );
}

NewsletterListNotificationComponent.displayName = 'NewsletterListNotification';
export const NewsletterListNotification = withBoundary(
  NewsletterListNotificationComponent,
);
