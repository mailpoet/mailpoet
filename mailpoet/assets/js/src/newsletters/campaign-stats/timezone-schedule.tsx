import { useMemo, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
  DataViews,
  filterSortAndPaginate,
  type Field,
  type View,
} from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import {
  formatTimezoneLabel,
  getBatchStatusLabel,
  getScheduledWindow,
  getTimezoneBreakdown,
  type TimezoneBreakdownEntry,
} from 'newsletters/timezone-campaign';
import { NewsletterType } from './newsletter-type';

type Props = {
  newsletter: NewsletterType;
};

type TimezoneRow = TimezoneBreakdownEntry & { id: number };

const DEFAULT_VIEW: View = {
  type: 'table',
  page: 1,
  perPage: 25,
  sort: { field: 'scheduledAt', direction: 'asc' },
  fields: ['scheduledAt', 'status', 'sent'],
  titleField: 'timezone',
  showTitle: true,
};

function formatDateTime(value: string): string {
  return `${MailPoet.Date.short(value)} ${MailPoet.Date.time(value)}`;
}

// Batch scheduled_at values are UTC; each row's send moment only makes sense
// as the wall clock of that batch's recipient time zone. Falls back to site
// time when the zone is not IANA-resolvable (e.g. an offset like "+02:00").
function formatDateTimeInZone(value: string, timeZone: string | null): string {
  if (timeZone) {
    try {
      return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone,
      }).format(new Date(`${value.replace(' ', 'T')}Z`));
    } catch {
      // fall through to site-time formatting
    }
  }
  return formatDateTime(value);
}

const fields: Field<TimezoneRow>[] = [
  {
    id: 'timezone',
    label: __('Time zone', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableGlobalSearch: false,
    getValue: ({ item }) =>
      formatTimezoneLabel(item.timezone, item.fallbackUsed),
    render: ({ item }) => (
      <span data-automation-id={`timezone_schedule_zone_${item.id}`}>
        {formatTimezoneLabel(item.timezone, item.fallbackUsed)}
      </span>
    ),
  },
  {
    id: 'scheduledAt',
    label: __('Scheduled for', 'mailpoet'),
    type: 'datetime',
    enableSorting: true,
    enableGlobalSearch: false,
    getValue: ({ item }) => item.scheduledAt ?? '',
    render: ({ item }) =>
      item.scheduledAt
        ? formatDateTimeInZone(item.scheduledAt, item.timezone)
        : '—',
  },
  {
    id: 'status',
    label: __('Status', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableGlobalSearch: false,
    getValue: ({ item }) => getBatchStatusLabel(item.status),
    render: ({ item }) => getBatchStatusLabel(item.status),
  },
  {
    id: 'sent',
    label: __('Sent', 'mailpoet'),
    enableSorting: true,
    enableGlobalSearch: false,
    getValue: ({ item }) => item.countProcessed,
    render: ({ item }) =>
      `${MailPoet.Num.toLocaleFixed(
        item.countProcessed,
      )} / ${MailPoet.Num.toLocaleFixed(item.countTotal)}`,
  },
];

export function TimezoneSchedule({ newsletter }: Props) {
  const [view, setView] = useState<View>(DEFAULT_VIEW);

  const rows = useMemo<TimezoneRow[]>(
    () =>
      getTimezoneBreakdown(newsletter.queue).map((entry, index) => ({
        ...entry,
        id: index,
      })),
    [newsletter.queue],
  );

  const { data, paginationInfo } = useMemo(
    () => filterSortAndPaginate(rows, view, fields),
    [rows, view],
  );

  const window = getScheduledWindow(newsletter.queue);

  return (
    <div
      className="mailpoet-stats-timezone-schedule"
      data-automation-id="timezone-schedule-stats"
    >
      {window && (
        <p>
          {
            // translators: %1$s is the date and time when the first time zone batch sends, %2$s when the last one sends.
            __('Sends from %1$s to %2$s', 'mailpoet')
              .replace('%1$s', formatDateTime(window.first))
              .replace('%2$s', formatDateTime(window.last))
          }
        </p>
      )}
      <div className="mailpoet-dataviews mailpoet-listing">
        <DataViews<TimezoneRow>
          data={data}
          fields={fields}
          view={view}
          onChangeView={setView}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => String(item.id)}
          empty={<p>{__('No time zone batches found.', 'mailpoet')}</p>}
        >
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>
    </div>
  );
}

TimezoneSchedule.displayName = 'TimezoneSchedule';
