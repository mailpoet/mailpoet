import jQuery from 'jquery';
import { Link, useParams } from 'react-router-dom';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Notice } from '@wordpress/components';
import {
  DataViews,
  type Action,
  type Field,
  type View,
} from '@wordpress/dataviews';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import {
  getDataViewsPreference,
  getStoredDataViewsFieldIds,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  filterToExtraParams,
  type ListingQueryParams,
} from 'common/dataviews';
import {
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';
import { formatTimezoneLabel } from 'newsletters/timezone-campaign';
import { buildHash, parseHash } from 'newsletters/listings/hash-state';
import {
  RESULT_FIELD,
  RESULT_VALUES,
  sanitizeSort,
  sendingStatusHashFilterToRequestFilter,
  sendingStatusRequestFilterToHashFilter,
  sendingStatusRequestFilterToViewFilters,
  sendingStatusViewFiltersToRequestFilter,
} from 'newsletters/listings/filters';
import {
  getSendingStatusSubscribers,
  resendFailedEmail,
  type SendingStatusApiError,
  type SendingStatusItem,
} from './api';

const listingPerPage = Number(window.mailpoet_listing_per_page);

const SUPPORTED_GROUPS = ['all'];

const LEGACY_GROUP_FILTER = { key: 'result', values: RESULT_VALUES };

const SORTABLE_FIELD_IDS = ['subscriberId', RESULT_FIELD];

const DEFAULT_SORT = { field: 'failed', direction: 'desc' as const };

type Newsletter = {
  id: string;
  subject: string;
  sent: boolean;
};

const statusLabels = {
  // translators: status when the sending of a newsletter has not been processed
  unprocessed: __('Unprocessed', 'mailpoet'),
  // translators: status when a newsletter has been sent
  sent: __('Sent', 'mailpoet'),
  // translators: status when the sending of a newsletter has failed
  failed: __('Failed', 'mailpoet'),
};

// translators: an email sending status: unprocessed, sent or failed
const sendingStatusLabel = __('Sending status', 'mailpoet');

function StatsLink({ newsletter }: { newsletter: Newsletter }) {
  if (!newsletter.id || !newsletter.subject || !newsletter.sent) return null;
  return (
    <p>
      <Link to={`/stats/${newsletter.id}`}>{newsletter.subject}</Link>
    </p>
  );
}

function buildFields(
  includeTimezone: boolean,
  resultLabels: Record<string, string>,
): Field<SendingStatusItem>[] {
  const fields: Field<SendingStatusItem>[] = [
    {
      id: 'subscriberId',
      label: __('Subscriber', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: true,
      getValue: ({ item }) => item.email,
      render: ({ item }) => (
        <div data-automation-id={`name_${item.taskId}_${item.subscriberId}`}>
          <a
            className="mailpoet-listing-title"
            href={`admin.php?page=mailpoet-subscribers#/edit/${item.subscriberId}`}
          >
            {item.email}
          </a>
          <div className="mailpoet-listing-subtitle">
            {`${item.firstName} ${item.lastName}`}
          </div>
        </div>
      ),
    },
    {
      id: RESULT_FIELD,
      label: sendingStatusLabel,
      type: 'text',
      getValue: ({ item }) => {
        if (!item.processed) return 'unprocessed';
        return item.failed ? 'failed' : 'sent';
      },
      elements: (
        Object.keys(statusLabels) as (keyof typeof statusLabels)[]
      ).map((value) => ({
        value,
        label: resultLabels[value] ?? statusLabels[value],
      })),
      enableSorting: true,
      enableGlobalSearch: false,
      render: ({ item }) => {
        let label = statusLabels.unprocessed as string;
        if (item.processed) {
          label = item.failed ? statusLabels.failed : statusLabels.sent;
        }
        return (
          <span
            data-automation-id={`status_${item.taskId}_${item.subscriberId}`}
          >
            {label}
          </span>
        );
      },
    },
    ...(includeTimezone
      ? [
          {
            id: 'timezone',
            label: __('Time zone', 'mailpoet'),
            enableSorting: false,
            enableGlobalSearch: false,
            render: ({ item }) => (
              <span
                data-automation-id={`timezone_${item.taskId}_${item.subscriberId}`}
              >
                {item.timezone !== null
                  ? formatTimezoneLabel(
                      item.timezone,
                      item.timezoneFallbackUsed,
                    )
                  : ''}
              </span>
            ),
          } as Field<SendingStatusItem>,
        ]
      : []),
    {
      id: 'error',
      label: __('Failure reason (if applicable)', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      render: ({ item }) => (
        <span data-automation-id={`error_${item.taskId}_${item.subscriberId}`}>
          {item.error ?? ''}
        </span>
      ),
    },
  ];
  // Only the result column maps to a request filter.
  return fields.map((field) =>
    field.id === RESULT_FIELD
      ? { ...field, filterBy: { operators: ['isAny'] } }
      : { ...field, filterBy: false },
  );
}

export function SendingStatus() {
  const params = useParams();
  const newsletterId = Number(params.id);
  const baseUrl = `sending-status/${params.id}`;

  const [newsletter, setNewsletter] = useState<Newsletter>({
    id: params.id ?? '',
    subject: '',
    sent: false,
  });

  useEffect(() => {
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: { id: newsletterId },
    })
      .done((res) =>
        setNewsletter({
          id: params.id ?? '',
          subject: res.data.subject,
          sent: res.data.sent_at !== null,
        }),
      )
      .fail((res) => MailPoet.Notice.showApiErrorNotice(res));
  }, [newsletterId, params.id]);

  const hashState = useMemo(
    () =>
      parseHash(
        window.location.hash,
        baseUrl,
        SUPPORTED_GROUPS,
        LEGACY_GROUP_FILTER,
      ),
    [baseUrl],
  );
  const [includeTimezone, setIncludeTimezone] = useState(false);
  // Result labels carry the counts from the latest response.
  const [resultLabels, setResultLabels] = useState<Record<string, string>>({});
  const fields = useMemo(
    () => buildFields(includeTimezone, resultLabels),
    [includeTimezone, resultLabels],
  );

  const getPreferredView = useCallback(
    () =>
      getDataViewsPreference(
        'sending-status',
        {
          type: 'table',
          perPage: listingPerPage,
          page: 1,
          sort: DEFAULT_SORT,
          fields: ['failed', 'error'],
          titleField: 'subscriberId',
          showTitle: true,
        },
        fields,
      ),
    [fields],
  );
  const [initialView] = useState<View>(() => {
    const preferredView = getPreferredView();
    return {
      ...preferredView,
      perPage: hashState.perPage ?? preferredView.perPage,
      page: hashState.page ?? 1,
      search: hashState.search,
      sort: sanitizeSort(
        {
          field:
            hashState.orderby ??
            preferredView.sort?.field ??
            DEFAULT_SORT.field,
          direction:
            hashState.order ??
            preferredView.sort?.direction ??
            DEFAULT_SORT.direction,
        },
        SORTABLE_FIELD_IDS,
        DEFAULT_SORT,
      ),
      filters: sendingStatusRequestFilterToViewFilters(
        sendingStatusHashFilterToRequestFilter(hashState.filter ?? {}),
      ),
    };
  });

  const load = useCallback(
    async (queryParams: ListingQueryParams, signal?: AbortSignal) => {
      const response = await getSendingStatusSubscribers(
        newsletterId,
        { ...queryParams, group: 'all' },
        signal,
      );
      // Keep surfacing mailer / cron problems on every reload; the REST
      // endpoint carries those envelope fields.
      const mtaLog =
        response.mta_log && typeof response.mta_log === 'object'
          ? response.mta_log
          : {};
      const state = {
        meta: { mta_log: mtaLog, cron_accessible: response.cron_accessible },
      };
      checkMailerStatus(state);
      checkCronStatus(state);
      return response;
    },
    [newsletterId],
  );

  const toRequestFilter = useCallback(
    (viewFilters: View['filters']) =>
      sendingStatusViewFiltersToRequestFilter(viewFilters),
    [],
  );

  const {
    view,
    setView,
    onChangeView,
    items,
    meta,
    groups,
    isLoading,
    error: loadError,
    clearError,
    refresh,
  } = useDataViewsQuery<SendingStatusItem>({
    initialView,
    load,
    extraParams: (currentView) =>
      filterToExtraParams(toRequestFilter(currentView.filters)),
  });
  const requestFilter = useMemo(
    () => toRequestFilter(view.filters),
    [toRequestFilter, view.filters],
  );
  useEffect(() => {
    const next = (groups ?? [])
      .filter((entry) => RESULT_VALUES.includes(entry.name))
      .reduce<Record<string, string>>(
        (labels, entry) => ({
          ...labels,
          [entry.name]: `${
            statusLabels[entry.name as keyof typeof statusLabels]
          } (${Number(entry.count).toLocaleString()})`,
        }),
        {},
      );
    if (Object.keys(next).length === 0) return;
    setResultLabels((current) =>
      JSON.stringify(current) === JSON.stringify(next) ? current : next,
    );
  }, [groups]);
  const persistedViewChange = usePersistedDataViewsPreference(
    'sending-status',
    view,
    onChangeView,
  );

  // The time zone column only exists for time zone campaigns, which is known
  // once items arrive. The mount-time preference read filtered the column out
  // (it was not an available field yet), so re-apply the user's raw stored
  // choice: keep it hidden when they removed it, restore it when they kept
  // it, and inject it after the status column when they never customized
  // columns at all.
  const timezoneColumnInjected = useRef(false);

  // The component stays mounted when the route id changes (e.g. navigating
  // between sending status pages), so the time zone column state of the
  // previous newsletter must not leak into the next one.
  useEffect(() => {
    timezoneColumnInjected.current = false;
    setIncludeTimezone(false);
    setView((currentView) =>
      currentView.fields?.includes('timezone')
        ? {
            ...currentView,
            fields: currentView.fields.filter((id) => id !== 'timezone'),
          }
        : currentView,
    );
  }, [newsletterId, setView]);

  useEffect(() => {
    if (timezoneColumnInjected.current) return;
    if (!items.some((item) => item.timezone !== null)) return;
    timezoneColumnInjected.current = true;
    setIncludeTimezone(true);
    const storedFields = getStoredDataViewsFieldIds('sending-status');
    setView((currentView) => {
      const viewFields = currentView.fields ?? [];
      if (viewFields.includes('timezone')) return currentView;
      if (storedFields) {
        if (!storedFields.includes('timezone')) return currentView;
        const knownIds = new Set([...viewFields, 'timezone']);
        return {
          ...currentView,
          fields: storedFields.filter((id) => knownIds.has(id)),
        };
      }
      const failedIndex = viewFields.indexOf('failed');
      const nextFields = [...viewFields];
      nextFields.splice(
        failedIndex >= 0 ? failedIndex + 1 : nextFields.length,
        0,
        'timezone',
      );
      return { ...currentView, fields: nextFields };
    });
  }, [items, setView]);

  useEffect(() => {
    // Compare against the preference-merged defaults, resolved at write time
    // (not the hardcoded ones, not a mount-time snapshot), so reloading a URL
    // without explicit params resolves to the same view it was written from.
    const preferredView = getPreferredView();
    const hash = buildHash(
      baseUrl,
      'all',
      view,
      sendingStatusRequestFilterToHashFilter(requestFilter),
      {
        sort: preferredView.sort?.field ?? DEFAULT_SORT.field,
        order: preferredView.sort?.direction ?? DEFAULT_SORT.direction,
        perPage: preferredView.perPage ?? listingPerPage,
      },
    );
    if (window.location.hash !== hash) {
      window.history.replaceState(null, '', hash);
    }
  }, [baseUrl, getPreferredView, requestFilter, view]);

  useEffect(() => {
    const applyHash = (): void => {
      const next = parseHash(
        window.location.hash,
        baseUrl,
        SUPPORTED_GROUPS,
        LEGACY_GROUP_FILTER,
      );
      clearError();
      // Fill hash segments the URL omits from the preference-merged defaults
      // (not the in-memory view) so back/forward resolves a URL exactly like
      // reopening it.
      const preferredView = getPreferredView();
      setView((currentView) => ({
        ...currentView,
        page: next.page ?? 1,
        perPage: next.perPage ?? preferredView.perPage,
        search: next.search ?? '',
        sort: sanitizeSort(
          {
            field:
              next.orderby ?? preferredView.sort?.field ?? DEFAULT_SORT.field,
            direction:
              next.order ??
              preferredView.sort?.direction ??
              DEFAULT_SORT.direction,
          },
          SORTABLE_FIELD_IDS,
          DEFAULT_SORT,
        ),
        filters: sendingStatusRequestFilterToViewFilters(
          sendingStatusHashFilterToRequestFilter(next.filter ?? {}),
        ),
      }));
    };
    window.addEventListener('hashchange', applyHash);
    return () => window.removeEventListener('hashchange', applyHash);
  }, [baseUrl, clearError, getPreferredView, setView]);

  // Auto-refresh on the WP heartbeat tick.
  useEffect(() => {
    const handler = (): void => refresh();
    jQuery(document).on('heartbeat-tick.mailpoet-sending-status', handler);
    return () => {
      jQuery(document).off('heartbeat-tick.mailpoet-sending-status', handler);
    };
  }, [refresh]);

  const resend = useCallback(
    (item: SendingStatusItem): void => {
      void resendFailedEmail(newsletterId, item.taskId, item.subscriberId)
        .then(() => refresh())
        .catch((error: SendingStatusApiError) => {
          MailPoet.Notice.error(
            error.message ||
              __(
                'The email could not be resent. Please try again.',
                'mailpoet',
              ),
            { scroll: true },
          );
        });
    },
    [newsletterId, refresh],
  );

  const actions = useMemo<Action<SendingStatusItem>[]>(
    () => [
      {
        id: 'resend',
        label: __('Resend', 'mailpoet'),
        context: 'single',
        isPrimary: true,
        supportsBulk: false,
        isEligible: (item) => Boolean(item.failed),
        callback: (targets) => {
          if (targets[0]) resend(targets[0]);
        },
      },
    ],
    [resend],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  // The legacy page showed this notice once the per-subscriber records had
  // been cleaned up: the email is sent, yet the unfiltered listing is empty.
  const showRetentionNotice =
    newsletter.sent &&
    !isLoading &&
    !loadError &&
    !view.search &&
    Object.keys(requestFilter).length === 0 &&
    meta.count === 0;

  return (
    <>
      <h1>
        {
          // translators: Page title. This page displays a list of emails along with their sending status: unprocessed, sent or failed.
          __('Sending status', 'mailpoet')
        }
      </h1>
      <StatsLink newsletter={newsletter} />

      {showRetentionNotice && (
        <p className="mailpoet-notice mailpoet-notice-info">
          {createInterpolateElement(
            __(
              'Sending status data is no longer available. Per-subscriber records for this newsletter have been cleaned up. You can adjust the retention period in <link>Advanced settings</link>.',
              'mailpoet',
            ),
            {
              link: <a href="admin.php?page=mailpoet-settings#/advanced"> </a>,
            },
          )}
        </p>
      )}

      {loadError && (
        <Notice status="error" onRemove={clearError}>
          {loadError === 'Failed to load data.'
            ? __('Failed to load the sending status.', 'mailpoet')
            : loadError}
        </Notice>
      )}

      <div
        className="mailpoet-dataviews mailpoet-listing"
        data-automation-id="sending_status_listing"
      >
        <DataViews<SendingStatusItem>
          data={items}
          fields={fields}
          view={view}
          onChangeView={persistedViewChange}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => `${item.taskId}-${item.subscriberId}`}
          isLoading={isLoading}
          empty={<p>{__('No sending task found.', 'mailpoet')}</p>}
        >
          <div className="mailpoet-dataviews__toolbar">
            <DataViews.Search label={__('Search', 'mailpoet')} />
            <DataViews.FiltersToggle />
            <div className="mailpoet-dataviews__toolbar-end">
              <DataViews.ViewConfig />
            </div>
          </div>
          <DataViews.Filters className="mailpoet-dataviews__filters" />
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>
    </>
  );
}

SendingStatus.displayName = 'SendingStatus';
