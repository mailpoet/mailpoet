import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { DataViews, View } from '@wordpress/dataviews';
import { __, _n, sprintf } from '@wordpress/i18n';

import { Button } from 'common';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  filterToExtraParams,
  type ListingQueryParams,
  useShowFiltersWhenActive,
} from 'common/dataviews';
import { getLogs, type LogListingItem } from './api';
import { DeleteLogsModal } from './delete-modal';
import { DownloadLogsModal } from './download-modal';
import { getLogActions, getLogFieldDefinitions, getLogFields } from './fields';
import {
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from './filters';
import {
  buildLogsUrl,
  LOGS_PAGE_SLUG,
  getDateRangeError,
  parseLogsUrlState,
  type LogsFilter,
} from './url-state';

const SETTINGS_URL = 'admin.php?page=mailpoet-settings#/advanced';

function SettingsLink({ children }: { children?: React.ReactNode }) {
  return (
    <a href={SETTINGS_URL} className="mailpoet-link">
      {children}
    </a>
  );
}

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'created_at', direction: 'desc' },
  fields: ['level', 'message', 'created_at'],
  titleField: 'name',
  showTitle: true,
};

type DownloadConfig = {
  action_url: string;
  nonce: string;
};

type Props = {
  defaultFrom: string;
  downloadConfig?: DownloadConfig;
  pageSlug?: string;
};

function buildInitialView(defaultFrom: string): View {
  const currentUrl = window.location.href;
  const state = parseLogsUrlState(currentUrl, defaultFrom);
  const searchParams = new URL(currentUrl).searchParams;
  const hasPerPageUrlState =
    searchParams.has('per_page') || searchParams.has('limit');
  const preferredView = getDataViewsPreference(
    'logs-v2',
    DEFAULT_VIEW,
    getLogFieldDefinitions(),
  );

  return {
    ...preferredView,
    page: state.page,
    perPage: hasPerPageUrlState ? state.perPage : preferredView.perPage,
    search: state.search,
    filters: requestFilterToViewFilters(state.filters),
  };
}

function filtersKey(view: View): string {
  return JSON.stringify(view.filters ?? []);
}

export function List({
  defaultFrom,
  downloadConfig,
  pageSlug = LOGS_PAGE_SLUG,
}: Props): JSX.Element {
  const initialView = useMemo(
    () => buildInitialView(defaultFrom),
    [defaultFrom],
  );
  const [expandedLogIds, setExpandedLogIds] = useState<Set<number>>(
    () => new Set(),
  );
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isDownloadModalOpen, setIsDownloadModalOpen] = useState(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const didMountRef = useRef(false);

  const load = useCallback(
    (params: ListingQueryParams, signal?: AbortSignal) => {
      if (getDateRangeError((params.filter as LogsFilter) ?? {})) {
        // Invalid bookmarked ranges are surfaced as a validation message
        // instead of being sent to REST (which would 400).
        return Promise.resolve({ items: [], meta: { count: 0, pages: 0 } });
      }
      return getLogs(
        { ...params, search: params.search?.trim() || undefined },
        signal,
      );
    },
    [],
  );

  const {
    view,
    items,
    meta,
    isLoading,
    error: loadError,
    onChangeView,
    clearError: clearLoadError,
    refresh,
  } = useDataViewsQuery<LogListingItem>({
    initialView,
    load,
    extraParams: (currentView) =>
      filterToExtraParams(viewFiltersToRequestFilter(currentView.filters)),
  });
  const dataViewsRef = useRef<HTMLDivElement>(null);
  useShowFiltersWhenActive(dataViewsRef, (view.filters ?? []).length > 0);

  const requestFilter = viewFiltersToRequestFilter(view.filters);
  const dateRangeError = getDateRangeError(requestFilter);
  const emptyState =
    loadError || dateRangeError ? null : (
      <div>
        <p>{__('No logs found for this period.', 'mailpoet')}</p>
        <p>
          {__(
            'Try a wider date range or clear the filters. If Logging is set to “Errors only” or “Nothing”, routine activity is not recorded. “Everything” records it, but switch it back after troubleshooting because the log grows fast.',
            'mailpoet',
          )}{' '}
          {createInterpolateElement(
            __('<a>Change the Logging setting</a>', 'mailpoet'),
            { a: <SettingsLink /> },
          )}
        </p>
      </div>
    );

  const searchTerm = view.search?.trim() || undefined;
  const isUnrestrictedDelete =
    Object.keys(requestFilter).length === 0 && !searchTerm;
  const canDelete = !isLoading && !dateRangeError && meta.count > 0;
  const canDownload = canDelete;

  const persistedViewChange = usePersistedDataViewsPreference(
    'logs-v2',
    view,
    onChangeView,
  );

  useEffect(() => {
    if (!didMountRef.current) {
      didMountRef.current = true;
      return;
    }

    const nextUrl = buildLogsUrl(
      window.location.href,
      view,
      viewFiltersToRequestFilter(view.filters),
      pageSlug,
    );
    window.history.replaceState(window.history.state, '', nextUrl);
  }, [view, pageSlug]);

  const viewFiltersKey = filtersKey(view);
  useEffect(() => {
    setExpandedLogIds((current) => (current.size > 0 ? new Set() : current));
  }, [viewFiltersKey, view.page, view.perPage, view.search]);

  const toggleExpanded = useCallback((logId: number): void => {
    setExpandedLogIds((current) => {
      const next = new Set(current);
      if (next.has(logId)) {
        next.delete(logId);
      } else {
        next.add(logId);
      }
      return next;
    });
  }, []);

  const fields = useMemo(
    () => getLogFields(expandedLogIds, view.search),
    [expandedLogIds, view.search],
  );
  const actions = useMemo(
    () => getLogActions(expandedLogIds, toggleExpanded),
    [expandedLogIds, toggleExpanded],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const retryLoading = useCallback((): void => {
    clearLoadError();
    refresh();
  }, [clearLoadError, refresh]);

  const handleDeleted = useCallback(
    (deleted: number): void => {
      setSuccessMessage(
        sprintf(
          _n('%s log deleted.', '%s logs deleted.', deleted, 'mailpoet'),
          deleted.toLocaleString(),
        ),
      );
      refresh();
    },
    [refresh],
  );

  return (
    <div
      ref={dataViewsRef}
      className="mailpoet-listing mailpoet-logs mailpoet-dataviews mailpoet-logs-dataviews"
    >
      <div className="mailpoet_notice notice inline">
        <p>
          {__(
            'MailPoet records what it does and the problems it runs into. The list shows the last 7 days by default; use the date filter to see older entries. Logs are deleted after 30 days. Search matches the message and the technical topic name (for example “mss”), not the readable label.',
            'mailpoet',
          )}{' '}
          {createInterpolateElement(
            __('<a>Change the Logging setting</a>', 'mailpoet'),
            { a: <SettingsLink /> },
          )}
        </p>
      </div>

      {successMessage && (
        <Notice status="success" onRemove={() => setSuccessMessage(null)}>
          {successMessage}
        </Notice>
      )}

      {loadError && (
        <Notice status="error" isDismissible={false}>
          <div className="mailpoet-logs-error">
            <span>{__('Logs could not be loaded.', 'mailpoet')}</span>
            <Button
              dimension="small"
              variant="secondary"
              onClick={retryLoading}
              isDisabled={isLoading}
            >
              {__('Retry', 'mailpoet')}
            </Button>
          </div>
        </Notice>
      )}

      {dateRangeError && (
        <Notice status="error" isDismissible={false}>
          <div className="mailpoet-logs-date-error" role="alert">
            {dateRangeError}
          </div>
        </Notice>
      )}

      <DataViews<LogListingItem>
        data={items}
        fields={fields}
        actions={actions}
        view={view}
        onChangeView={persistedViewChange}
        paginationInfo={paginationInfo}
        defaultLayouts={{ table: {} }}
        getItemId={(item) => String(item.id)}
        isLoading={isLoading}
        empty={emptyState}
      >
        <div className="mailpoet-logs-dataviews__toolbar">
          <DataViews.Search label={__('Search logs', 'mailpoet')} />
          <DataViews.FiltersToggle />
          <div className="mailpoet-dataviews__toolbar-end">
            <Button
              dimension="small"
              variant="destructive"
              onClick={() => setIsDeleteModalOpen(true)}
              isDisabled={!canDelete}
            >
              {__('Delete logs...', 'mailpoet')}
            </Button>
            {downloadConfig && (
              <Button
                dimension="small"
                variant="tertiary"
                onClick={() => setIsDownloadModalOpen(true)}
                isDisabled={!canDownload}
              >
                {__('Download...', 'mailpoet')}
              </Button>
            )}
            <DataViews.ViewConfig />
          </div>
        </div>
        <DataViews.FiltersToggled className="mailpoet-dataviews__filters" />
        <DataViews.Layout />
        <DataViews.Footer />
      </DataViews>
      {isDeleteModalOpen && (
        <DeleteLogsModal
          count={meta.count}
          filter={requestFilter}
          search={searchTerm}
          isUnrestricted={isUnrestrictedDelete}
          onClose={() => setIsDeleteModalOpen(false)}
          onDeleted={handleDeleted}
        />
      )}
      {isDownloadModalOpen && downloadConfig && (
        <DownloadLogsModal
          count={meta.count}
          filter={requestFilter}
          search={searchTerm}
          isUnrestricted={isUnrestrictedDelete}
          downloadConfig={downloadConfig}
          onClose={() => setIsDownloadModalOpen(false)}
        />
      )}
    </div>
  );
}

List.displayName = 'LogsList';
