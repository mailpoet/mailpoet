import classnames from 'classnames';
import jQuery from 'jquery';
import { Button, Notice } from '@wordpress/components';
import {
  DataViews,
  type Action,
  type Field,
  type View,
} from '@wordpress/dataviews';
import {
  useCallback,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
  type SetStateAction,
} from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { __ } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import { MailerError } from 'notices/mailer-error';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  type ListingGroup,
  type ListingQueryParams,
  DataViewsFooter,
} from 'common/dataviews';
import { Select } from 'common/form/select/select';
import { pollExportStatus } from 'newsletters/statistics-export/poll-export-status';
import {
  bulkAction,
  getNewsletters,
  onNewslettersListingExtras,
  type NewsletterApiError,
  type NewsletterListingItem,
  type NewslettersBulkAction,
  type NewslettersBulkResult,
  type NewslettersBulkScope,
  type NewslettersListingParams,
  type NewsletterType,
} from '../api';
import { checkCronStatus, checkMailerStatus } from './utils.jsx';
import { buildHash, parseHash } from './hash-state';

const listingPerPage = Number(window.mailpoet_listing_per_page);

type Group = string;

function formatCount(count: number): string {
  return count.toLocaleString();
}

const DEFAULT_GROUPS = ['all', 'trash'] as const;
export const STANDARD_NEWSLETTER_GROUPS = [
  'all',
  'draft',
  'scheduled',
  'sending',
  'sent',
  'trash',
] as const;
export const ACTIVATION_NEWSLETTER_GROUPS = [
  'all',
  'active',
  'draft',
  'trash',
] as const;
export const NOTIFICATION_HISTORY_GROUPS = [
  'all',
  'sending',
  'sent',
  'trash',
] as const;

export type NewsletterMailerLog = {
  status: string;
  error?: unknown;
  [key: string]: unknown;
};

export function toNewsletterMailerLog(value: unknown): NewsletterMailerLog {
  if (value && typeof value === 'object') {
    const record = value as Record<string, unknown>;
    return {
      ...record,
      status: typeof record.status === 'string' ? record.status : '',
    };
  }
  return { status: '' };
}

export type NewslettersListingProps = {
  type: NewsletterType;
  baseUrl: string;
  parentId?: number;
  fields: Field<NewsletterListingItem>[];
  defaultFields: string[];
  itemActions?: (
    helpers: ListingActionHelpers,
  ) => Action<NewsletterListingItem>[];
  defaultSort: { field: string; direction: 'asc' | 'desc' };
  emptyState?: (count: number) => ReactNode;
  supportsExportStats?: boolean;
  supportedGroups?: readonly string[];
  // Optional custom row id resolver (defaults to item.id).
  getItemId?: (item: NewsletterListingItem) => string;
  // Called whenever the listing data reloads, so callers can reconcile
  // optimistic UI state (e.g. status toggles) against the server response.
  onItemsLoaded?: (items: NewsletterListingItem[]) => void;
};

export type ListingActionHelpers = {
  refresh: () => void;
  navigate: (path: string) => void;
  location: { pathname: string };
};

const trashLabels = {
  one: __('1 email was moved to the trash.', 'mailpoet'),
  many: __('%1$d emails were moved to the trash.', 'mailpoet'),
};
const deleteLabels = {
  one: __('1 email was permanently deleted.', 'mailpoet'),
  many: __('%1$d emails were permanently deleted.', 'mailpoet'),
};
const restoreLabels = {
  one: __('1 email has been restored from the Trash.', 'mailpoet'),
  many: __('%1$d emails have been restored from the Trash.', 'mailpoet'),
};

// `export_stats` is async: the endpoint only queues a task. Mirror the legacy
// listing — tell the user the export is queued, then poll the task and trigger
// the download once the file is ready.
function handleExportStatsResult(
  result: NewslettersBulkResult,
  format: string,
): void {
  if (!result.task_id) {
    MailPoet.Notice.error(__('Could not start the export.', 'mailpoet'), {
      scroll: true,
    });
    return;
  }
  MailPoet.Notice.success(
    __(
      'Export queued. The download will start automatically when it is ready.',
      'mailpoet',
    ),
    { timeout: 6000 },
  );
  pollExportStatus(result.task_id, {
    onComplete: (status) => {
      if (!status.exportFileURL) {
        MailPoet.Notice.error(
          __('The export file could not be generated.', 'mailpoet'),
          { scroll: true },
        );
        return;
      }
      MailPoet.trackEvent('Email statistics export completed', {
        'File Format': format,
        'Export Type': 'bulk',
      });
      window.location.href = status.exportFileURL;
    },
    onError: (message) => {
      MailPoet.Notice.error(message, { scroll: true });
    },
  });
}

function actionSuccessNotice(
  action: NewslettersBulkAction,
  count: number,
): void {
  const formatted = formatCount(count);
  if (action === 'trash') {
    MailPoet.Notice.success(
      count === 1
        ? trashLabels.one
        : trashLabels.many.replace('%1$d', formatted),
    );
  } else if (action === 'delete') {
    MailPoet.Notice.success(
      count === 1
        ? deleteLabels.one
        : deleteLabels.many.replace('%1$d', formatted),
    );
  } else if (action === 'restore') {
    MailPoet.Notice.success(
      count === 1
        ? restoreLabels.one
        : restoreLabels.many.replace('%1$d', formatted),
    );
  }
}

export function NewslettersListing({
  type,
  baseUrl,
  parentId,
  fields,
  defaultFields,
  itemActions,
  defaultSort,
  emptyState,
  supportsExportStats = false,
  supportedGroups = DEFAULT_GROUPS,
  getItemId,
  onItemsLoaded,
}: NewslettersListingProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const hashState = useMemo(
    () => parseHash(window.location.hash, baseUrl, [...supportedGroups]),
    [baseUrl, supportedGroups],
  );
  const [group, setGroup] = useState<Group>(hashState.group ?? 'all');
  const [filter, setFilter] = useState<Record<string, string>>(
    hashState.filter ?? {},
  );
  const [selection, setSelection] = useState<string[]>([]);
  const [mailerExtras, setMailerExtras] = useState<{
    method: string | null;
    log: NewsletterMailerLog;
  }>({ method: null, log: { status: '' } });

  const [defaultViewBase] = useState<View>(() => ({
    type: 'table',
    perPage: listingPerPage,
    page: 1,
    sort: defaultSort,
    fields: defaultFields,
    // `fields` always leads with the subject/name column; that is the row
    // title. `defaultFields` lists only the *other* visible columns, so the
    // title renders once and is never duplicated as a regular column.
    titleField: fields[0]?.id,
    showTitle: true,
  }));
  const getPreferredView = useCallback(
    () =>
      getDataViewsPreference(`newsletters-${type}`, defaultViewBase, fields),
    [defaultViewBase, fields, type],
  );
  const [initialView] = useState<View>(() => {
    const preferredView = getPreferredView();
    return {
      ...preferredView,
      perPage: hashState.perPage ?? preferredView.perPage,
      page: hashState.page ?? 1,
      search: hashState.search,
      sort: {
        field:
          hashState.orderby ?? preferredView.sort?.field ?? defaultSort.field,
        direction:
          hashState.order ??
          preferredView.sort?.direction ??
          defaultSort.direction,
      },
    };
  });

  const load = useCallback(
    (params: ListingQueryParams, signal?: AbortSignal) => {
      const listingParams: NewslettersListingParams = {
        ...params,
        group,
        filter,
        type,
      };
      if (parentId !== undefined) listingParams.parent_id = parentId;
      return getNewsletters(listingParams, signal);
    },
    [filter, group, parentId, type],
  );

  const {
    view,
    setView,
    onChangeView,
    items,
    meta,
    filters,
    groups,
    isLoading,
    error: loadError,
    clearError: clearLoadError,
    refresh,
  } = useDataViewsQuery<NewsletterListingItem>({
    initialView,
    load,
  });

  // Mailer + cron envelope info is dispatched through `checkMailerStatus` /
  // `checkCronStatus` every time the listing reloads. The REST endpoint
  // exposes those values through a module-level subscription so we keep the
  // side-effect wiring identical from the UI's perspective.
  useEffect(
    () =>
      onNewslettersListingExtras((extras) => {
        const mtaLog = toNewsletterMailerLog(extras.mta_log);
        const adapted = { meta: { ...extras, mta_log: mtaLog } };
        checkMailerStatus(adapted);
        checkCronStatus(adapted);
        setMailerExtras({ method: extras.mta_method ?? null, log: mtaLog });
      }),
    [],
  );

  useEffect(() => {
    onItemsLoaded?.(items);
  }, [items, onItemsLoaded]);

  // Auto-refresh on WP heartbeat tick, but skip refreshes while the user has
  // an active selection so we don't yank rows out from under a bulk action.
  useEffect(() => {
    const handler = (): void => {
      if (selection.length === 0) refresh();
    };
    jQuery(document).on('heartbeat-tick.mailpoet-listing', handler);
    return () => {
      jQuery(document).off('heartbeat-tick.mailpoet-listing', handler);
    };
  }, [refresh, selection.length]);

  useEffect(() => {
    // Compare against the preference-merged defaults, resolved at write time
    // (not the hardcoded ones, not a mount-time snapshot), so reloading a URL
    // without explicit params resolves to the same view it was written from.
    const preferredView = getPreferredView();
    const hash = buildHash(baseUrl, group, view, filter, {
      sort: preferredView.sort?.field ?? defaultSort.field,
      order: preferredView.sort?.direction ?? defaultSort.direction,
      perPage: preferredView.perPage ?? listingPerPage,
    });
    if (window.location.hash !== hash) {
      window.history.replaceState(null, '', hash);
    }
  }, [
    baseUrl,
    defaultSort.direction,
    defaultSort.field,
    filter,
    getPreferredView,
    group,
    view,
  ]);

  useEffect(() => {
    const applyHash = (): void => {
      const next = parseHash(window.location.hash, baseUrl, [
        ...supportedGroups,
      ]);
      setGroup(next.group ?? 'all');
      setFilter(next.filter ?? {});
      setSelection([]);
      clearLoadError();
      // Fill hash segments the URL omits from the preference-merged defaults
      // (not the in-memory view) so back/forward resolves a URL exactly like
      // reopening it.
      const preferredView = getPreferredView();
      setView((currentView) => ({
        ...currentView,
        page: next.page ?? 1,
        perPage: next.perPage ?? preferredView.perPage,
        search: next.search ?? '',
        sort: {
          field: next.orderby ?? preferredView.sort?.field ?? defaultSort.field,
          direction:
            next.order ??
            preferredView.sort?.direction ??
            defaultSort.direction,
        },
      }));
    };
    window.addEventListener('hashchange', applyHash);
    return () => window.removeEventListener('hashchange', applyHash);
  }, [
    baseUrl,
    clearLoadError,
    defaultSort.direction,
    defaultSort.field,
    getPreferredView,
    setView,
    supportedGroups,
  ]);

  const listingParams = useMemo<NewslettersBulkScope>(
    () => ({
      type,
      group,
      filter,
      search: view.search ?? '',
      parent_id: parentId,
    }),
    [filter, group, parentId, type, view.search],
  );

  const handleApiError = useCallback((error: NewsletterApiError) => {
    MailPoet.Notice.error(
      error.message ||
        __('The action could not be completed. Please try again.', 'mailpoet'),
      { scroll: true },
    );
  }, []);

  const executeBulkAction = useCallback(
    async (
      action: NewslettersBulkAction,
      selectedIds: number[],
      selectAll = false,
      extra: Record<string, unknown> = {},
      scope: NewslettersBulkScope = listingParams,
    ): Promise<void> => {
      if (selectedIds.length === 0 && !selectAll) return;
      try {
        const response = await bulkAction(
          action,
          { ...scope, selection: selectedIds, select_all: selectAll },
          extra,
        );
        setSelection([]);
        if (action === 'export_stats') {
          const format =
            typeof extra.format === 'string' ? extra.format : 'csv';
          handleExportStatsResult(response.data, format);
          return;
        }
        actionSuccessNotice(action, response.data.count);
        refresh();
      } catch (error) {
        handleApiError(error as NewsletterApiError);
      }
    },
    [handleApiError, listingParams, refresh],
  );

  const runBulkAction = useCallback(
    async (
      action: NewslettersBulkAction,
      targets: NewsletterListingItem[],
      extra: Record<string, unknown> = {},
    ): Promise<void> => {
      const selectedIds = targets
        .map((item) => Number(item.id))
        .filter((id) => Number.isFinite(id));
      await executeBulkAction(action, selectedIds, false, extra);
    },
    [executeBulkAction],
  );

  const handleViewChange = useCallback(
    (nextView: View) => {
      setSelection([]);
      onChangeView(nextView);
    },
    [onChangeView],
  );
  const persistedViewChange = usePersistedDataViewsPreference(
    `newsletters-${type}`,
    view,
    handleViewChange,
  );

  const handleSelectionChange = useCallback(
    (nextSelection: SetStateAction<string[]>): void => {
      setSelection(nextSelection);
    },
    [],
  );

  const handleGroupSelect = useCallback(
    (nextGroup: Group): void => {
      if (nextGroup === group) return;
      setGroup(nextGroup);
      setSelection([]);
      clearLoadError();
      setView((currentView) => ({ ...currentView, page: 1 }));
    },
    [clearLoadError, group, setView],
  );

  const handleFilterSelect = useCallback(
    (name: string, value: string): void => {
      setFilter((current) => {
        const next = { ...current };
        if (value) next[name] = value;
        else delete next[name];
        return next;
      });
      setSelection([]);
      setView((currentView) => ({ ...currentView, page: 1 }));
    },
    [setView],
  );

  const groupCounts = useMemo(() => {
    const counts: Record<string, number | null> = { all: null, trash: null };
    supportedGroups.forEach((g) => {
      counts[g] = null;
    });
    (groups ?? []).forEach((entry: ListingGroup) => {
      if (entry.name in counts) {
        counts[entry.name] = entry.count;
      }
    });
    return counts;
  }, [groups, supportedGroups]);

  // If the user is in trash with no items and no filters/search active, bounce
  // them back to All.
  useEffect(() => {
    if (
      group === 'trash' &&
      !isLoading &&
      !loadError &&
      groupCounts.trash === 0 &&
      !view.search &&
      Object.keys(filter).length === 0
    ) {
      setGroup('all');
      setSelection([]);
      setView((currentView) => ({ ...currentView, page: 1 }));
    }
  }, [
    filter,
    group,
    groupCounts.trash,
    isLoading,
    loadError,
    setView,
    view.search,
  ]);

  const tabs = useMemo(
    () =>
      (groups ?? []).filter(
        (entry) =>
          entry.name in groupCounts &&
          !(entry.name === 'trash' && entry.count === 0 && group !== 'trash'),
      ),
    [group, groupCounts, groups],
  );

  const actionHelpers = useMemo<ListingActionHelpers>(
    () => ({
      refresh,
      navigate: (path) => navigate(path),
      location: { pathname: location.pathname },
    }),
    [location.pathname, navigate, refresh],
  );

  const baseBulkActions = useMemo<Action<NewsletterListingItem>[]>(() => {
    const list: Action<NewsletterListingItem>[] = [
      {
        id: 'trash',
        label: __('Move to trash', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          void runBulkAction('trash', targets);
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group === 'trash',
        callback: (targets) => {
          void runBulkAction('restore', targets);
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group === 'trash',
        callback: (targets) => {
          void runBulkAction('delete', targets);
        },
      },
    ];
    if (supportsExportStats) {
      list.push({
        id: 'export_stats',
        label: __('Export statistics', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        // Only sent emails have statistics to export.
        isEligible: (item) => group !== 'trash' && item.status === 'sent',
        callback: (targets) => {
          void runBulkAction('export_stats', targets, { format: 'csv' });
        },
      });
    }
    return list;
  }, [group, runBulkAction, supportsExportStats]);

  const actions = useMemo<Action<NewsletterListingItem>[]>(() => {
    const extra = itemActions ? itemActions(actionHelpers) : [];
    return [...extra, ...baseBulkActions];
  }, [actionHelpers, baseBulkActions, itemActions]);

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const handleEmptyTrash = useCallback(() => {
    // Empty Trash clears the whole trash for this email type, ignoring any
    // active search term or list filter that scopes the current view.
    void executeBulkAction(
      'delete',
      [],
      true,
      {},
      {
        type,
        group: 'trash',
        parent_id: parentId,
      },
    );
  }, [executeBulkAction, parentId, type]);

  const availableFilters = useMemo(
    () =>
      Object.keys(filters).filter((name) => {
        const opts = filters[name] ?? [];
        return !(opts.length === 0 || (opts.length === 1 && !opts[0].value));
      }),
    [filters],
  );

  // The `all` group already aggregates every non-trash status, so summing all
  // groups would double-count. The listing is empty only when both `all` and
  // `trash` are known and report zero.
  const isEmpty = useMemo(() => {
    const { all, trash } = groupCounts;
    if (all === null || trash === null) return false;
    return all === 0 && trash === 0;
  }, [groupCounts]);

  if (isEmpty && emptyState) {
    return <>{emptyState(0)}</>;
  }

  return (
    <div>
      {mailerExtras.method && (
        <MailerError
          mtaMethod={mailerExtras.method}
          mtaLog={mailerExtras.log as unknown as MtaLog}
        />
      )}

      {loadError && (
        <Notice status="error" onRemove={clearLoadError}>
          {loadError === 'Failed to load data.'
            ? __('Failed to load emails.', 'mailpoet')
            : loadError}
        </Notice>
      )}

      <div className="mailpoet-categories mailpoet-dataviews__tabs mailpoet-newsletters-dataviews__tabs">
        <div className="components-tab-panel__tabs" role="tablist">
          {tabs.map((entry) => {
            const tabClasses = classnames(
              'components-button',
              'components-tab-panel__tabs-item',
              `mailpoet-dataviews-group-${entry.name}`,
              {
                'is-active': entry.name === group,
              },
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
        className="mailpoet-dataviews mailpoet-newsletters-dataviews mailpoet-listing"
        data-automation-id={`newsletters_listing_${type}`}
      >
        <DataViews<NewsletterListingItem>
          data={items}
          fields={fields}
          view={view}
          onChangeView={persistedViewChange}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => (getItemId ? getItemId(item) : String(item.id))}
          selection={selection}
          onChangeSelection={handleSelectionChange}
          isLoading={isLoading}
          empty={
            <p>
              {view.search
                ? __('No emails found.', 'mailpoet')
                : __(
                    "Nothing here yet! But, don't fret - there's no reason to get upset. Pretty soon, you’ll be sending emails faster than a turbo-jet.",
                    'mailpoet',
                  )}
            </p>
          }
        >
          <div className="mailpoet-dataviews__toolbar mailpoet-newsletters-dataviews__toolbar">
            <DataViews.Search label={__('Search', 'mailpoet')} />
            {(availableFilters.length > 0 || group === 'trash') && (
              <div className="mailpoet-listing-filters">
                {availableFilters.map((name) => (
                  <Select
                    isMinWidth
                    dimension="small"
                    key={`filter-${name}`}
                    name={name}
                    value={filter[name] ?? ''}
                    automationId={`listing_filter_${name}`}
                    onChange={(event) =>
                      handleFilterSelect(name, event.currentTarget.value)
                    }
                  >
                    {filters[name].map((option) => (
                      <option
                        value={option.value}
                        key={`filter-option-${option.value}`}
                      >
                        {option.label}
                      </option>
                    ))}
                  </Select>
                ))}
                {group === 'trash' && (
                  <Button
                    variant="secondary"
                    size="compact"
                    data-automation-id="empty_trash"
                    onClick={handleEmptyTrash}
                  >
                    {__('Empty Trash', 'mailpoet')}
                  </Button>
                )}
              </div>
            )}
            <div className="mailpoet-dataviews__toolbar-end">
              <DataViews.ViewConfig />
            </div>
          </div>
          <DataViews.Layout />
          <DataViewsFooter
            view={view}
            onChangeView={persistedViewChange}
            paginationInfo={paginationInfo}
            isLoading={isLoading}
            hasData={items.length > 0}
          />
        </DataViews>
      </div>
    </div>
  );
}
