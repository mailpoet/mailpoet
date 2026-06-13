import {
  __experimentalConfirmDialog as ConfirmDialog,
  Notice,
  TabPanel,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  type ListingQueryParams,
  type ListingResponse,
} from 'common/dataviews';
import { storeName } from './store/constants';
import type { AutomationItem } from './store/types';
import { AutomationStatus } from './automation';
import { MailPoet } from '../../mailpoet';
import { PageHeader } from '../../common/page-header';
import { automationFields } from './fields';
import { filtersToParam } from './filters';
import { getAutomationAnalyticsUrl, getAutomationEditorUrl } from './urls';

type Group =
  | 'all'
  | AutomationStatus.ACTIVE
  | AutomationStatus.DRAFT
  | AutomationStatus.TRASH;

type AutomationBulkAction = 'duplicate' | 'trash' | 'restore' | 'delete';

type PendingAction = {
  action: Extract<AutomationBulkAction, 'trash' | 'delete'>;
  targets: AutomationItem[];
} | null;

const DEFAULT_PER_PAGE = 25;

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: DEFAULT_PER_PAGE,
  page: 1,
  fields: ['subscribers', 'status'],
  titleField: 'name',
  descriptionField: 'description',
  showTitle: true,
  showDescription: true,
  layout: {
    styles: {
      subscribers: { minWidth: '360px' },
      status: { width: '120px' },
    },
  },
};

const groupNames: Group[] = [
  'all',
  AutomationStatus.ACTIVE,
  AutomationStatus.DRAFT,
  AutomationStatus.TRASH,
];

function parsePositiveInt(value: string | null): number | undefined {
  if (!value) return undefined;
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function getGroupFromSearch(search: URLSearchParams): Group {
  const status = search.get('status');
  return groupNames.includes(status as Group) ? (status as Group) : 'all';
}

function getViewFromSearch(
  search: URLSearchParams,
  defaultView = DEFAULT_VIEW,
): View {
  const order = search.get('order');
  const orderby = search.get('orderby');
  return {
    ...defaultView,
    page: parsePositiveInt(search.get('paged')) ?? defaultView.page,
    perPage: parsePositiveInt(search.get('per_page')) ?? defaultView.perPage,
    search: search.get('search') ?? undefined,
    sort:
      orderby && (order === 'asc' || order === 'desc')
        ? { field: orderby, direction: order }
        : defaultView.sort,
  };
}

function viewMatchesSearch(
  view: View,
  search: URLSearchParams,
  defaultView = DEFAULT_VIEW,
): boolean {
  const searchView = getViewFromSearch(search, defaultView);
  return (
    (view.page ?? 1) === (searchView.page ?? 1) &&
    (view.perPage ?? DEFAULT_PER_PAGE) ===
      (searchView.perPage ?? DEFAULT_PER_PAGE) &&
    (view.search ?? '') === (searchView.search ?? '') &&
    (view.sort?.field ?? '') === (searchView.sort?.field ?? '') &&
    (view.sort?.direction ?? '') === (searchView.sort?.direction ?? '')
  );
}

function formatTabTitle(label: string, count: number | null): string {
  return count !== null && count > 0 ? `${label} (${count})` : label;
}

// Map the active listing tab to the status set sent to the endpoint. The "all"
// tab shows every non-trashed automation, matching the previous UI.
function statusParamForGroup(group?: string): string[] {
  if (!group || group === 'all') {
    return [
      AutomationStatus.ACTIVE,
      AutomationStatus.DRAFT,
      AutomationStatus.DEACTIVATING,
    ];
  }
  return [group];
}

// The "subscribers" column sorts by how many subscribers entered, which the
// endpoint exposes as the synthetic `entered` order-by field.
function mapSortField(field?: string): string | undefined {
  return field === 'subscribers' ? 'entered' : field;
}

function legacyMatchesGroup(item: AutomationItem, group: Group): boolean {
  if (group === 'all') return item.status !== AutomationStatus.TRASH;
  return item.status === group;
}

// Server-side loader for the real automations. Legacy automations are not
// served here — they are overlaid client-side in the default view only.
const loadAutomationsListing = async (
  params: ListingQueryParams,
  signal?: AbortSignal,
): Promise<ListingResponse<AutomationItem>> => {
  const orderby = mapSortField(params.orderby);
  const query: Record<string, unknown> = {
    page: params.page,
    per_page: params.per_page,
    order: params.order,
    status: statusParamForGroup(params.group),
  };
  if (params.search) query.search = params.search;
  if (orderby) query.orderby = orderby;
  if (params.filter) query.filter = params.filter;

  const response = await apiFetch<{ data: ListingResponse<AutomationItem> }>({
    path: addQueryArgs('/automations', query),
    method: 'GET',
    signal,
  });
  return response.data;
};

function getActionCopy(pendingAction: PendingAction): {
  title: string;
  message: string;
  confirmText: string;
} {
  if (!pendingAction) return { title: '', message: '', confirmText: '' };
  const count = pendingAction.targets.length;
  const names = pendingAction.targets
    .map((automation) => `"${automation.name}"`)
    .join(', ');

  if (pendingAction.action === 'trash') {
    return {
      title: _n('Trash automation', 'Trash automations', count, 'mailpoet'),
      message: sprintf(
        // translators: %s is the list of automation names.
        _n(
          'Are you sure you want to move the automation %s to the Trash?',
          'Are you sure you want to move the automations %s to the Trash?',
          count,
          'mailpoet',
        ),
        names,
      ),
      confirmText: __('Yes, move to trash', 'mailpoet'),
    };
  }

  return {
    title: _n(
      'Permanently delete automation',
      'Permanently delete automations',
      count,
      'mailpoet',
    ),
    message: sprintf(
      // translators: %s is the list of automation names.
      _n(
        'Are you sure you want to permanently delete %s and all associated data? This cannot be undone!',
        'Are you sure you want to permanently delete %s and all associated data? This cannot be undone!',
        count,
        'mailpoet',
      ),
      names,
    ),
    confirmText: __('Yes, permanently delete', 'mailpoet'),
  };
}

function getAutomationActionTargets(
  action: AutomationBulkAction,
  targets: AutomationItem[],
): AutomationItem[] {
  switch (action) {
    case 'duplicate':
      return targets.filter(
        (item) => !item.isLegacy && item.status !== AutomationStatus.TRASH,
      );
    case 'trash':
      return targets.filter((item) => item.status !== AutomationStatus.TRASH);
    case 'restore':
    case 'delete':
      return targets.filter((item) => item.status === AutomationStatus.TRASH);
    default:
      return [];
  }
}

function getBulkActionSuccessMessage(
  action: AutomationBulkAction,
  count: number,
): string {
  if (action === 'duplicate') {
    return sprintf(
      // translators: %d is the number of automations.
      _n(
        '%d automation was duplicated.',
        '%d automations were duplicated.',
        count,
        'mailpoet',
      ),
      count,
    );
  }
  if (action === 'trash') {
    return sprintf(
      // translators: %d is the number of automations.
      _n(
        '%d automation was moved to the trash.',
        '%d automations were moved to the trash.',
        count,
        'mailpoet',
      ),
      count,
    );
  }
  if (action === 'restore') {
    return sprintf(
      // translators: %d is the number of automations.
      _n(
        '%d automation was restored from the trash.',
        '%d automations were restored from the trash.',
        count,
        'mailpoet',
      ),
      count,
    );
  }
  return sprintf(
    // translators: %d is the number of automations.
    _n(
      '%d automation was permanently deleted.',
      '%d automations were permanently deleted.',
      count,
      'mailpoet',
    ),
    count,
  );
}

const createSuccessNotice = (content: string): void => {
  void dispatch(noticesStore).createSuccessNotice(content);
};

export function AutomationListingHeader(): JSX.Element {
  return (
    <PageHeader heading={__('Automations', 'mailpoet')}>
      <a href={MailPoet.urls.automationTemplates} className="page-title-action">
        {__('Add new automation', 'mailpoet')}
      </a>
    </PageHeader>
  );
}

export function AutomationListing(): JSX.Element {
  const navigate = useNavigate();
  const location = useLocation();
  const defaultView = useMemo(
    () => getDataViewsPreference('automation', DEFAULT_VIEW, automationFields),
    [],
  );
  const [group, setGroup] = useState<Group>(() =>
    getGroupFromSearch(new URLSearchParams(location.search)),
  );
  const [selection, setSelection] = useState<string[]>([]);
  const [pendingAction, setPendingAction] = useState<PendingAction>(null);

  const legacyAutomations = useSelect(
    (select) => select(storeName).getLegacyAutomations(),
    [],
  );
  const {
    loadLegacyAutomations,
    restoreAutomation,
    restoreLegacyAutomation,
    duplicateAutomation,
    trashAutomation,
    trashLegacyAutomation,
    deleteAutomation,
    deleteLegacyAutomation,
  } = useDispatch(storeName);

  const [initialView] = useState<View>(() =>
    getViewFromSearch(new URLSearchParams(location.search), defaultView),
  );

  const groupRef = useRef(group);
  groupRef.current = group;
  const extraParams = useCallback(
    (currentView: View): Partial<ListingQueryParams> => {
      const filter = filtersToParam(currentView.filters);
      return {
        group: groupRef.current,
        ...(Object.keys(filter).length > 0 ? { filter } : {}),
      };
    },
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
    refresh,
    clearError,
  } = useDataViewsQuery<AutomationItem>({
    initialView,
    load: loadAutomationsListing,
    extraParams,
  });

  const latestGroupRef = useRef(group);
  const latestViewRef = useRef(view);
  useEffect(() => {
    latestGroupRef.current = group;
    latestViewRef.current = view;
  }, [group, view]);

  useEffect(() => {
    void loadLegacyAutomations();
  }, [loadLegacyAutomations]);

  const updateUrlSearchString = useCallback(
    (nextGroup: Group, nextView: View) => {
      const newSearch = new URLSearchParams(location.search);

      newSearch.set('status', nextGroup);
      if ((nextView.page ?? 1) > 1) {
        newSearch.set('paged', String(nextView.page));
      } else {
        newSearch.delete('paged');
      }
      const defaultPerPage =
        getDataViewsPreference('automation', DEFAULT_VIEW, automationFields)
          .perPage ?? DEFAULT_PER_PAGE;
      if ((nextView.perPage ?? defaultPerPage) !== defaultPerPage) {
        newSearch.set('per_page', String(nextView.perPage));
      } else {
        newSearch.delete('per_page');
      }
      if (nextView.search) {
        newSearch.set('search', nextView.search);
      } else {
        newSearch.delete('search');
      }
      if (nextView.sort) {
        newSearch.set('orderby', nextView.sort.field);
        newSearch.set('order', nextView.sort.direction);
      } else {
        newSearch.delete('orderby');
        newSearch.delete('order');
      }

      const currentSearch = location.search.startsWith('?')
        ? location.search.slice(1)
        : location.search;
      const nextSearch = newSearch.toString();
      if (nextSearch !== currentSearch) {
        navigate({ search: nextSearch });
      }
    },
    [location.search, navigate],
  );

  // Sync external URL changes (browser back/forward, ?status= deep-links) back
  // into the listing state.
  useEffect(() => {
    const nextSearch = new URLSearchParams(location.search);
    const nextGroup = getGroupFromSearch(nextSearch);
    const currentDefaultView = getDataViewsPreference(
      'automation',
      DEFAULT_VIEW,
      automationFields,
    );
    let selectionShouldBeCleared = false;
    if (nextGroup !== latestGroupRef.current) {
      setGroup(nextGroup);
      selectionShouldBeCleared = true;
    }
    if (
      !viewMatchesSearch(latestViewRef.current, nextSearch, currentDefaultView)
    ) {
      setView(getViewFromSearch(nextSearch, currentDefaultView));
      selectionShouldBeCleared = true;
    }
    if (selectionShouldBeCleared) {
      setSelection([]);
    }
  }, [location.search, setView]);

  const handleViewChange = useCallback(
    (nextView: View) => {
      setSelection([]);
      onChangeView(nextView);
      updateUrlSearchString(group, nextView);
    },
    [group, onChangeView, updateUrlSearchString],
  );
  const persistedViewChange = usePersistedDataViewsPreference(
    'automation',
    view,
    handleViewChange,
  );

  const handleTabSelect = (tabName: string): void => {
    if (!groupNames.includes(tabName as Group)) return;
    const nextGroup = tabName as Group;
    if (nextGroup === group) return;

    const nextView = { ...view, page: 1 };
    setGroup(nextGroup);
    setSelection([]);
    setView(nextView);
    updateUrlSearchString(nextGroup, nextView);
  };

  // Legacy automations are only shown while browsing the default view (no
  // filter / search / sort). Any active filter, search, or sort switches to the
  // server-filtered real automations only.
  const isDefaultView = useMemo(
    () => !view.search && !view.sort && (view.filters?.length ?? 0) === 0,
    [view.search, view.sort, view.filters],
  );

  const legacyForDisplay = useMemo(() => {
    if (!isDefaultView || (view.page ?? 1) !== 1) return [];
    return (legacyAutomations ?? []).filter((item) =>
      legacyMatchesGroup(item, group),
    );
  }, [isDefaultView, view.page, legacyAutomations, group]);

  const data = useMemo(
    () => [...items, ...legacyForDisplay],
    [items, legacyForDisplay],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const tabs = useMemo(() => {
    const realCounts: Record<Group, number | null> = {
      all: null,
      [AutomationStatus.ACTIVE]: null,
      [AutomationStatus.DRAFT]: null,
      [AutomationStatus.TRASH]: null,
    };
    (groups ?? []).forEach((entry) => {
      if (entry.name in realCounts) {
        realCounts[entry.name as Group] = entry.count;
      }
    });
    const total = (name: Group): number | null => {
      const realCount = realCounts[name];
      if (realCount === null) return null;
      const legacy = isDefaultView
        ? (legacyAutomations ?? []).filter((item) =>
            legacyMatchesGroup(item, name),
          ).length
        : 0;
      return realCount + legacy;
    };
    return [
      {
        name: 'all',
        title: formatTabTitle(__('All', 'mailpoet'), total('all')),
        className: 'mailpoet-dataviews-group-all mailpoet-tab-all',
      },
      {
        name: AutomationStatus.ACTIVE,
        title: formatTabTitle(
          __('Active', 'mailpoet'),
          total(AutomationStatus.ACTIVE),
        ),
        className: 'mailpoet-tab-active',
      },
      {
        name: AutomationStatus.DRAFT,
        title: formatTabTitle(
          _x('Inactive', 'noun', 'mailpoet'),
          total(AutomationStatus.DRAFT),
        ),
        className: 'mailpoet-tab-draft',
      },
      {
        name: AutomationStatus.TRASH,
        title: formatTabTitle(
          _x('Trash', 'noun', 'mailpoet'),
          total(AutomationStatus.TRASH),
        ),
        className: 'mailpoet-dataviews-group-trash mailpoet-tab-trash',
      },
    ];
  }, [groups, isDefaultView, legacyAutomations]);

  const runAutomationAction = useCallback(
    async (
      action: AutomationBulkAction,
      targets: AutomationItem[],
    ): Promise<void> => {
      const actionTargets = getAutomationActionTargets(action, targets);
      if (actionTargets.length === 0) return;

      const showsIndividualNotices = actionTargets.length === 1;
      const noticeOptions = { showSuccessNotice: showsIndividualNotices };

      await Promise.all(
        actionTargets.map((automation) => {
          if (action === 'duplicate') {
            return duplicateAutomation(
              automation,
              noticeOptions,
            ) as Promise<void>;
          }
          if (action === 'trash') {
            const selectedAction = automation.isLegacy
              ? trashLegacyAutomation
              : trashAutomation;
            return selectedAction(automation, noticeOptions) as Promise<void>;
          }
          if (action === 'restore') {
            return automation.isLegacy
              ? (restoreLegacyAutomation(
                  automation,
                  noticeOptions,
                ) as Promise<void>)
              : (restoreAutomation(
                  automation,
                  AutomationStatus.DRAFT,
                  noticeOptions,
                ) as Promise<void>);
          }

          const selectedAction = automation.isLegacy
            ? deleteLegacyAutomation
            : deleteAutomation;
          return selectedAction(automation, noticeOptions) as Promise<void>;
        }),
      );

      if (!showsIndividualNotices) {
        createSuccessNotice(
          getBulkActionSuccessMessage(action, actionTargets.length),
        );
      }

      setSelection([]);
      refresh();
      void loadLegacyAutomations();
    },
    [
      deleteAutomation,
      deleteLegacyAutomation,
      duplicateAutomation,
      loadLegacyAutomations,
      refresh,
      restoreAutomation,
      restoreLegacyAutomation,
      trashAutomation,
      trashLegacyAutomation,
    ],
  );

  const runPendingAction = useCallback((): void => {
    if (!pendingAction) return;
    void runAutomationAction(pendingAction.action, pendingAction.targets);
    setPendingAction(null);
  }, [pendingAction, runAutomationAction]);

  const actions = useMemo<Action<AutomationItem>[]>(
    () => [
      {
        id: 'analytics',
        label: __('Analytics', 'mailpoet'),
        isPrimary: true,
        supportsBulk: false,
        callback: (targets) => {
          if (targets[0]) {
            window.location.href = getAutomationAnalyticsUrl(targets[0]);
          }
        },
      },
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        icon: 'edit',
        isPrimary: true,
        supportsBulk: false,
        callback: (targets) => {
          if (targets[0]) {
            window.location.href = getAutomationEditorUrl(targets[0]);
          }
        },
      },
      {
        id: 'duplicate',
        label: __('Duplicate', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) =>
          !item.isLegacy && item.status !== AutomationStatus.TRASH,
        callback: (targets) => {
          void runAutomationAction('duplicate', targets);
        },
      },
      {
        id: 'trash',
        label: _x('Trash', 'verb', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => item.status !== AutomationStatus.TRASH,
        callback: (targets) => {
          const actionTargets = getAutomationActionTargets('trash', targets);
          if (actionTargets.length > 0) {
            setPendingAction({ action: 'trash', targets: actionTargets });
          }
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => item.status === AutomationStatus.TRASH,
        callback: (targets) => {
          void runAutomationAction('restore', targets);
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => item.status === AutomationStatus.TRASH,
        callback: (targets) => {
          const actionTargets = getAutomationActionTargets('delete', targets);
          if (actionTargets.length > 0) {
            setPendingAction({ action: 'delete', targets: actionTargets });
          }
        },
      },
    ],
    [runAutomationAction],
  );

  const emptyLabel =
    group === AutomationStatus.TRASH
      ? __('Trash is empty.', 'mailpoet')
      : __('No automations found.', 'mailpoet');
  const actionCopy = getActionCopy(pendingAction);
  const listingIsLoading =
    isLoading || (isDefaultView && legacyAutomations === undefined);

  return (
    <>
      {loadError && (
        <Notice status="error" onRemove={clearError}>
          {loadError}
        </Notice>
      )}
      <TabPanel
        key={group}
        className="mailpoet-dataviews__tabs"
        activeClass="is-active"
        tabs={tabs}
        initialTabName={group}
        onSelect={handleTabSelect}
      >
        {() => (
          <div
            className="mailpoet-dataviews mailpoet-automation-dataviews"
            data-automation-id="automation_listing"
          >
            <DataViews<AutomationItem>
              key={group}
              data={data}
              fields={automationFields}
              view={view}
              onChangeView={persistedViewChange}
              actions={actions}
              paginationInfo={paginationInfo}
              defaultLayouts={{ table: {} }}
              getItemId={(item) =>
                `${item.isLegacy ? 'legacy' : 'automation'}-${item.id}`
              }
              selection={selection}
              onChangeSelection={setSelection}
              isLoading={listingIsLoading}
              empty={<div>{emptyLabel}</div>}
            >
              <div className="mailpoet-dataviews__toolbar">
                <DataViews.Search
                  label={__('Search automations', 'mailpoet')}
                />
                <DataViews.FiltersToggle />
                <div className="mailpoet-dataviews__toolbar-end">
                  <DataViews.ViewConfig />
                </div>
              </div>
              <DataViews.Filters />
              <DataViews.Layout />
              <DataViews.Footer />
            </DataViews>
          </div>
        )}
      </TabPanel>
      <ConfirmDialog
        className="mailpoet-confirm-dialog"
        isOpen={!!pendingAction}
        title={actionCopy.title}
        confirmButtonText={actionCopy.confirmText}
        __experimentalHideHeader={false}
        onConfirm={runPendingAction}
        onCancel={() => setPendingAction(null)}
      >
        {actionCopy.message}
      </ConfirmDialog>
    </>
  );
}
