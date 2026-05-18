import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Notice } from '@wordpress/components';
import { DataViews, View } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

import { Button } from 'common';
import { useDataViewsQuery, type ListingQueryParams } from 'common/dataviews';
import { Datepicker } from '../common/datepicker/datepicker';
import { getLogs, type LogListingItem } from './api';
import { getLogFields } from './fields';
import {
  buildLogsUrl,
  dateFromString,
  formatDateAsYmd,
  getDateRangeError,
  parseLogsUrlState,
  type DateFilters,
} from './url-state';

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'created_at', direction: 'desc' },
  fields: ['message', 'action', 'created_at'],
  titleField: 'name',
  showTitle: true,
};

type Props = {
  defaultFrom: string;
};

function buildInitialView(defaultFrom: string): {
  view: View;
  dateFilters: DateFilters;
} {
  const state = parseLogsUrlState(window.location.href, defaultFrom);

  return {
    view: {
      ...DEFAULT_VIEW,
      page: state.page,
      perPage: state.perPage,
      search: state.search,
    },
    dateFilters: state.dateFilters,
  };
}

export function List({ defaultFrom }: Props): JSX.Element {
  const initialState = useMemo(
    () => buildInitialView(defaultFrom),
    [defaultFrom],
  );
  const [dateFilters, setDateFilters] = useState<DateFilters>(
    initialState.dateFilters,
  );
  const [pendingDateFilters, setPendingDateFilters] = useState<DateFilters>(
    initialState.dateFilters,
  );
  const [expandedLogIds, setExpandedLogIds] = useState<Set<number>>(
    () => new Set(),
  );
  const didMountRef = useRef(false);

  const load = useCallback(
    (params: ListingQueryParams) => {
      if (getDateRangeError(dateFilters)) {
        return Promise.resolve({
          items: [],
          meta: { count: 0, pages: 0 },
          filters: [],
          groups: [],
        });
      }
      const search = params.search?.trim();
      return getLogs({
        ...params,
        search: search || undefined,
        filter: dateFilters,
      });
    },
    [dateFilters],
  );

  const {
    view,
    setView,
    items,
    meta,
    isLoading,
    error: loadError,
    clearError: clearLoadError,
    refresh,
  } = useDataViewsQuery<LogListingItem>({
    initialView: initialState.view,
    load,
  });

  const dateRangeError = getDateRangeError(pendingDateFilters);

  const updateView = useCallback(
    (nextView: View) => {
      const searchChanged = (nextView.search ?? '') !== (view.search ?? '');
      const perPageChanged = nextView.perPage !== view.perPage;

      setView({
        ...nextView,
        page: searchChanged || perPageChanged ? 1 : nextView.page,
      });
    },
    [setView, view],
  );

  useEffect(() => {
    if (!didMountRef.current) {
      didMountRef.current = true;
      return;
    }

    const nextUrl = buildLogsUrl(window.location.href, view, dateFilters);
    window.history.replaceState({}, '', nextUrl);
  }, [dateFilters, view]);

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
    () => getLogFields(expandedLogIds, toggleExpanded),
    [expandedLogIds, toggleExpanded],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const applyDateFilters = useCallback((): void => {
    if (dateRangeError) {
      return;
    }
    setDateFilters(pendingDateFilters);
    setView((currentView) => ({ ...currentView, page: 1 }));
  }, [dateRangeError, pendingDateFilters, setView]);

  const clearDateFilters = useCallback((): void => {
    const emptyFilters: DateFilters = {};
    setPendingDateFilters(emptyFilters);
    setDateFilters(emptyFilters);
    setView((currentView) => ({ ...currentView, page: 1 }));
  }, [setView]);

  const retryLoading = useCallback((): void => {
    clearLoadError();
    refresh();
  }, [clearLoadError, refresh]);

  return (
    <div className="mailpoet-listing mailpoet-logs mailpoet-logs-dataviews">
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

      <DataViews<LogListingItem>
        data={items}
        fields={fields}
        view={view}
        onChangeView={updateView}
        paginationInfo={paginationInfo}
        defaultLayouts={{ table: {} }}
        getItemId={(item) => String(item.id)}
        isLoading={isLoading}
        empty={<div>{__('No logs found.', 'mailpoet')}</div>}
      >
        <div className="mailpoet-logs-dataviews__toolbar">
          <DataViews.Search label={__('Search logs', 'mailpoet')} />
          <div className="mailpoet-logs-date-filters">
            <label
              className="mailpoet-logs-date-filter"
              htmlFor="mailpoet-logs-from"
            >
              <span>{__('From', 'mailpoet')}</span>
              <Datepicker
                id="mailpoet-logs-from"
                dateFormat="MMMM d, yyyy"
                onChange={(date: Date | null): void =>
                  setPendingDateFilters((current) => ({
                    ...current,
                    from: formatDateAsYmd(date),
                  }))
                }
                maxDate={new Date()}
                selected={dateFromString(pendingDateFilters.from)}
                dimension="small"
                disabled={isLoading}
                isClearable
                aria-label={__('Filter logs from date', 'mailpoet')}
              />
            </label>
            <label
              className="mailpoet-logs-date-filter"
              htmlFor="mailpoet-logs-to"
            >
              <span>{__('To', 'mailpoet')}</span>
              <Datepicker
                id="mailpoet-logs-to"
                dateFormat="MMMM d, yyyy"
                onChange={(date: Date | null): void =>
                  setPendingDateFilters((current) => ({
                    ...current,
                    to: formatDateAsYmd(date),
                  }))
                }
                maxDate={new Date()}
                selected={dateFromString(pendingDateFilters.to)}
                dimension="small"
                disabled={isLoading}
                isClearable
                aria-label={__('Filter logs to date', 'mailpoet')}
              />
            </label>
            <Button
              dimension="small"
              onClick={applyDateFilters}
              isDisabled={isLoading || Boolean(dateRangeError)}
            >
              {__('Apply', 'mailpoet')}
            </Button>
            <Button
              dimension="small"
              variant="secondary"
              onClick={clearDateFilters}
              isDisabled={isLoading}
            >
              {__('Clear', 'mailpoet')}
            </Button>
          </div>
          {dateRangeError && (
            <div className="mailpoet-logs-date-error" role="alert">
              {dateRangeError}
            </div>
          )}
        </div>
        <DataViews.Layout />
        <DataViews.Footer />
      </DataViews>
    </div>
  );
}

List.displayName = 'LogsList';
