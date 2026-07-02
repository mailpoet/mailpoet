import {
  __experimentalConfirmDialog as ConfirmDialog,
  TabPanel,
} from '@wordpress/components';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import {
  DataViews,
  filterSortAndPaginate,
  View,
  Action,
} from '@wordpress/dataviews';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  DataViewsFooter,
} from 'common/dataviews';
import { storeName } from './store/constants';
import type { AutomationItem } from './store/types';
import { AutomationStatus } from './automation';
import { automationCount, legacyAutomationCount } from '../config';
import { MailPoet } from '../../mailpoet';
import { PageHeader } from '../../common/page-header';
import { automationFields } from './fields';
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

function formatTabTitle(label: string, count: number): string {
  return count > 0 ? `${label} (${count})` : label;
}

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
  const [view, setView] = useState<View>(() =>
    getViewFromSearch(new URLSearchParams(location.search), defaultView),
  );
  const [selection, setSelection] = useState<string[]>([]);
  const [pendingAction, setPendingAction] = useState<PendingAction>(null);
  const latestGroupRef = useRef(group);
  const latestViewRef = useRef(view);

  const automations = useSelect((select) =>
    select(storeName).getAllAutomations(),
  );
  const {
    loadAutomations,
    loadLegacyAutomations,
    restoreAutomation,
    restoreLegacyAutomation,
    duplicateAutomation,
    trashAutomation,
    trashLegacyAutomation,
    deleteAutomation,
    deleteLegacyAutomation,
  } = useDispatch(storeName);

  useEffect(() => {
    void loadAutomations();
    void loadLegacyAutomations();
  }, [loadAutomations, loadLegacyAutomations]);

  useEffect(() => {
    latestGroupRef.current = group;
    latestViewRef.current = view;
  }, [group, view]);

  useEffect(() => {
    const nextSearch = new URLSearchParams(location.search);
    const nextGroup = getGroupFromSearch(nextSearch);
    // Resolve omitted URL params against the current preference-merged
    // defaults so browser navigation restores the view the URL was written
    // against.
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
  }, [location.search]);

  const updateUrlSearchString = useCallback(
    (nextGroup: Group, nextView: View) => {
      const newSearch = new URLSearchParams(location.search);

      newSearch.set('status', nextGroup);
      if ((nextView.page ?? 1) > 1) {
        newSearch.set('paged', String(nextView.page));
      } else {
        newSearch.delete('paged');
      }
      // Compare against the preference-merged default, resolved at write time
      // (not the hardcoded one, not a mount-time snapshot), so reloading a
      // URL without `per_page` resolves to the same view it was written from.
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

  const handleViewChange = useCallback(
    (nextView: View) => {
      setSelection([]);
      setView(nextView);
      updateUrlSearchString(group, nextView);
    },
    [group, updateUrlSearchString],
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

  const groupedAutomations = useMemo<Record<Group, AutomationItem[]>>(() => {
    const grouped: Record<Group, AutomationItem[]> = {
      all: [],
      [AutomationStatus.ACTIVE]: [],
      [AutomationStatus.DRAFT]: [],
      [AutomationStatus.TRASH]: [],
    };
    (automations ?? []).forEach((automation) => {
      if (automation.status in grouped) {
        grouped[automation.status as Group].push(automation);
      }
      if (automation.status !== AutomationStatus.TRASH) {
        grouped.all.push(automation);
      }
    });
    return grouped;
  }, [automations]);

  const totalCount = automationCount + legacyAutomationCount;
  const { data, paginationInfo } = useMemo(() => {
    if (!automations) {
      return {
        data: [],
        paginationInfo: {
          totalItems: totalCount,
          totalPages: Math.ceil(
            totalCount / (view.perPage ?? DEFAULT_PER_PAGE),
          ),
        },
      };
    }
    const filteredAutomations = groupedAutomations[group] ?? [];
    return filterSortAndPaginate(filteredAutomations, view, automationFields);
  }, [automations, group, groupedAutomations, totalCount, view]);

  useEffect(() => {
    const currentPage = view.page ?? 1;
    const lastPage = paginationInfo.totalPages;
    if (automations && lastPage > 0 && currentPage > lastPage) {
      handleViewChange({ ...view, page: lastPage });
    }
  }, [automations, handleViewChange, paginationInfo.totalPages, view]);

  const tabs = useMemo(
    () => [
      {
        name: 'all',
        title: formatTabTitle(
          __('All', 'mailpoet'),
          groupedAutomations.all.length,
        ),
        className: 'mailpoet-dataviews-group-all mailpoet-tab-all',
      },
      {
        name: AutomationStatus.ACTIVE,
        title: formatTabTitle(
          __('Active', 'mailpoet'),
          groupedAutomations[AutomationStatus.ACTIVE].length,
        ),
        className: 'mailpoet-tab-active',
      },
      {
        name: AutomationStatus.DRAFT,
        title: formatTabTitle(
          _x('Inactive', 'noun', 'mailpoet'),
          groupedAutomations[AutomationStatus.DRAFT].length,
        ),
        className: 'mailpoet-tab-draft',
      },
      {
        name: AutomationStatus.TRASH,
        title: formatTabTitle(
          _x('Trash', 'noun', 'mailpoet'),
          groupedAutomations[AutomationStatus.TRASH].length,
        ),
        className: 'mailpoet-dataviews-group-trash mailpoet-tab-trash',
      },
    ],
    [groupedAutomations],
  );

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
    },
    [
      deleteAutomation,
      deleteLegacyAutomation,
      duplicateAutomation,
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

  return (
    <>
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
              isLoading={!automations}
              empty={<div>{emptyLabel}</div>}
            >
              <div className="mailpoet-dataviews__toolbar">
                <DataViews.Search
                  label={__('Search automations', 'mailpoet')}
                />
                <div className="mailpoet-dataviews__toolbar-end">
                  <DataViews.ViewConfig />
                </div>
              </div>
              <DataViews.Layout />
              <DataViewsFooter
                view={view}
                onChangeView={persistedViewChange}
                paginationInfo={paginationInfo}
                isLoading={!automations}
                hasData={data.length > 0}
              />
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
