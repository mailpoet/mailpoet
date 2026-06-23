import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Notice } from '@wordpress/components';
import { DataViews, View } from '@wordpress/dataviews';
import { __, _n, sprintf } from '@wordpress/i18n';

import { Button } from 'common';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  filterToExtraParams,
  type ListingQueryParams,
} from 'common/dataviews';
import { getLogs, type LogListingItem } from './api';
import { DeleteLogsModal } from './delete-modal';
import { getLogActions, getLogFieldDefinitions, getLogFields } from './fields';
import {
  getLogFilterOptions,
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from './filters';
import {
  buildLogsUrl,
  getDateRangeError,
  parseLogsUrlState,
  type LogsFilter,
} from './url-state';

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'created_at', direction: 'desc' },
  fields: ['message', 'created_at'],
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
};

function buildInitialView(defaultFrom: string): View {
  const currentUrl = window.location.href;
  const state = parseLogsUrlState(currentUrl, defaultFrom);
  const searchParams = new URL(currentUrl).searchParams;
  const hasPerPageUrlState =
    searchParams.has('per_page') || searchParams.has('limit');
  const preferredView = getDataViewsPreference(
    'logs',
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

export function List({ defaultFrom, downloadConfig }: Props): JSX.Element {
  const initialView = useMemo(
    () => buildInitialView(defaultFrom),
    [defaultFrom],
  );
  const [expandedLogIds, setExpandedLogIds] = useState<Set<number>>(
    () => new Set(),
  );
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
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

  const requestFilter = viewFiltersToRequestFilter(view.filters);
  const dateRangeError = getDateRangeError(requestFilter);
  const emptyState =
    loadError || dateRangeError ? null : (
      <div>{__('No logs found.', 'mailpoet')}</div>
    );

  const searchTerm = view.search?.trim() || undefined;
  const isUnrestrictedDelete =
    Object.keys(requestFilter).length === 0 && !searchTerm;
  const canDelete = !isLoading && !dateRangeError && meta.count > 0;

  const persistedViewChange = usePersistedDataViewsPreference(
    'logs',
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
    );
    window.history.replaceState({}, '', nextUrl);
  }, [view]);

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
    () => getLogFields(expandedLogIds, getLogFilterOptions()),
    [expandedLogIds],
  );
  const actions = useMemo(
    () => getLogActions(expandedLogIds, toggleExpanded),
    [expandedLogIds, toggleExpanded],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const downloadLogs = useCallback((): void => {
    if (!downloadConfig) return;
    const params = new URLSearchParams({
      action: 'mailpoet_download_logs',
      _wpnonce: downloadConfig.nonce,
    });
    if (requestFilter.from) params.set('from', requestFilter.from);
    if (requestFilter.to) params.set('to', requestFilter.to);
    window.location.assign(`${downloadConfig.action_url}?${params.toString()}`);
  }, [downloadConfig, requestFilter]);


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
    <div className="mailpoet-listing mailpoet-logs mailpoet-dataviews mailpoet-logs-dataviews">
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
            <DataViews.ViewConfig />
            {downloadConfig && (
              <Button
                dimension="small"
                variant="secondary"
                onClick={downloadLogs}
                isDisabled={isLoading}
              >
                {__('Download', 'mailpoet')}
              </Button>
            )}
          </div>
        </div>
        <DataViews.Filters />
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
    </div>
  );
}

List.displayName = 'LogsList';
