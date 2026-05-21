import classnames from 'classnames';
import jQuery from 'jquery';
import { Link, useParams } from 'react-router-dom';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Notice } from '@wordpress/components';
import {
  DataViews,
  type Action,
  type Field,
  type View,
} from '@wordpress/dataviews';
import { createInterpolateElement } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import {
  useDataViewsQuery,
  type ListingGroup,
  type ListingQueryParams,
} from 'common/dataviews';
import {
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';
import { buildHash, parseHash } from 'newsletters/listings/hash-state';
import {
  getSendingStatusSubscribers,
  resendFailedEmail,
  type SendingStatusApiError,
  type SendingStatusItem,
} from './api';

const listingPerPage = Number(window.mailpoet_listing_per_page);

const SUPPORTED_GROUPS = ['all', 'sent', 'failed', 'unprocessed'] as const;

const DEFAULT_SORT = { field: 'failed', direction: 'desc' as const };

type Newsletter = {
  id: string;
  subject: string;
  sent: boolean;
};

const statusLabels = {
  unprocessed: _x(
    'Unprocessed',
    'status when the sending of a newsletter has not been processed',
    'mailpoet',
  ),
  sent: _x('Sent', 'status when a newsletter has been sent', 'mailpoet'),
  failed: _x(
    'Failed',
    'status when the sending of a newsletter has failed',
    'mailpoet',
  ),
};

const sendingStatusLabel = _x(
  'Sending status',
  'an email sending status: unprocessed, sent or failed.',
  'mailpoet',
);

function StatsLink({ newsletter }: { newsletter: Newsletter }) {
  if (!newsletter.id || !newsletter.subject || !newsletter.sent) return null;
  return (
    <p>
      <Link to={`/stats/${newsletter.id}`}>{newsletter.subject}</Link>
    </p>
  );
}

function buildFields(): Field<SendingStatusItem>[] {
  return [
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
      id: 'failed',
      label: sendingStatusLabel,
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
    () => parseHash(window.location.hash, baseUrl, [...SUPPORTED_GROUPS]),
    [baseUrl],
  );
  const [group, setGroup] = useState<string>(hashState.group ?? 'all');

  const [initialView] = useState<View>(() => ({
    type: 'table',
    perPage: hashState.perPage ?? listingPerPage,
    page: hashState.page ?? 1,
    search: hashState.search,
    sort: {
      field: hashState.orderby ?? DEFAULT_SORT.field,
      direction: hashState.order ?? DEFAULT_SORT.direction,
    },
    fields: ['failed', 'error'],
    titleField: 'subscriberId',
    showTitle: true,
  }));

  const load = useCallback(
    async (queryParams: ListingQueryParams, signal?: AbortSignal) => {
      const response = await getSendingStatusSubscribers(
        newsletterId,
        { ...queryParams, group },
        signal,
      );
      // The legacy listing surfaced mailer / cron problems on every reload via
      // these checks; the REST endpoint carries the same envelope fields.
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
    [group, newsletterId],
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
  } = useDataViewsQuery<SendingStatusItem>({ initialView, load });

  useEffect(() => {
    const hash = buildHash(
      baseUrl,
      group,
      view,
      {},
      {
        sort: DEFAULT_SORT.field,
        order: DEFAULT_SORT.direction,
        perPage: listingPerPage,
      },
    );
    if (window.location.hash !== hash) {
      window.history.replaceState(null, '', hash);
    }
  }, [baseUrl, group, view]);

  useEffect(() => {
    const applyHash = (): void => {
      const next = parseHash(window.location.hash, baseUrl, [
        ...SUPPORTED_GROUPS,
      ]);
      setGroup(next.group ?? 'all');
      clearError();
      setView((currentView) => ({
        ...currentView,
        page: next.page ?? 1,
        perPage: next.perPage ?? currentView.perPage,
        search: next.search ?? '',
        sort: {
          field: next.orderby ?? currentView.sort?.field ?? DEFAULT_SORT.field,
          direction:
            next.order ?? currentView.sort?.direction ?? DEFAULT_SORT.direction,
        },
      }));
    };
    window.addEventListener('hashchange', applyHash);
    return () => window.removeEventListener('hashchange', applyHash);
  }, [baseUrl, clearError, setView]);

  // Auto-refresh on the WP heartbeat tick, mirroring the legacy listing.
  useEffect(() => {
    const handler = (): void => refresh();
    jQuery(document).on('heartbeat-tick.mailpoet-sending-status', handler);
    return () => {
      jQuery(document).off('heartbeat-tick.mailpoet-sending-status', handler);
    };
  }, [refresh]);

  const handleGroupSelect = useCallback(
    (nextGroup: string): void => {
      if (nextGroup === group) return;
      setGroup(nextGroup);
      clearError();
      setView((currentView) => ({ ...currentView, page: 1 }));
    },
    [clearError, group, setView],
  );

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

  const fields = useMemo(() => buildFields(), []);

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

  const tabs = useMemo(
    () =>
      (groups ?? []).filter((entry) =>
        (SUPPORTED_GROUPS as readonly string[]).includes(entry.name),
      ),
    [groups],
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
    group === 'all' &&
    !view.search &&
    meta.count === 0;

  return (
    <>
      <h1>
        {_x(
          'Sending status',
          'Page title. This page displays a list of emails along with their sending status: unprocessed, sent or failed.',
          'mailpoet',
        )}
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

      <div className="mailpoet-categories mailpoet-dataviews__tabs">
        <div className="components-tab-panel__tabs" role="tablist">
          {tabs.map((entry: ListingGroup) => {
            const tabClasses = classnames(
              'components-button',
              'components-tab-panel__tabs-item',
              `mailpoet-dataviews-group-${entry.name}`,
              { 'is-active': entry.name === group },
            );
            return (
              <a
                key={entry.name}
                href="#"
                role="tab"
                aria-selected={entry.name === group}
                className={tabClasses}
                data-automation-id={`filters_${entry.name}`}
                onClick={(event) => {
                  event.preventDefault();
                  handleGroupSelect(entry.name);
                }}
              >
                <span data-title={entry.label}>{entry.label}</span>
                {Number(entry.count) > 0 && (
                  <span className="count">
                    {Number(entry.count).toLocaleString()}
                  </span>
                )}
              </a>
            );
          })}
        </div>
      </div>

      <div
        className="mailpoet-dataviews mailpoet-listing"
        data-automation-id="sending_status_listing"
      >
        <DataViews<SendingStatusItem>
          data={items}
          fields={fields}
          view={view}
          onChangeView={onChangeView}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => `${item.taskId}-${item.subscriberId}`}
          isLoading={isLoading}
          empty={<p>{__('No sending task found.', 'mailpoet')}</p>}
        >
          <div className="mailpoet-dataviews__toolbar">
            <DataViews.Search label={__('Search', 'mailpoet')} />
          </div>
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>
    </>
  );
}

SendingStatus.displayName = 'SendingStatus';
