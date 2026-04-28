import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
  __experimentalConfirmDialog as ConfirmDialog,
  Notice,
  TabPanel,
} from '@wordpress/components';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { escapeHTML } from '@wordpress/escape-html';
import { useDataViewsQuery, type ListingQueryParams } from 'common/dataviews';
import { Notices } from './list/notices';
import { getSegmentsQuery, updateSegmentsQuery } from './list/query';
import * as ROUTES from '../routes';
import { PageHeader } from '../../common/page-header';
import { SubscribersCacheMessage } from '../../common/subscribers-cache-message';
import { SubscribersInPlan } from '../../common/subscribers-in-plan';
import { TopBarWithBoundary } from '../../common/top-bar/top-bar';
import { MailPoet } from '../../mailpoet';
import { MssAccessNotices } from '../../notices/mss-access-notices';
import {
  bulkAction,
  getDynamicSegments,
  type DynamicSegmentBulkAction,
  type DynamicSegmentBulkActionResult,
  type DynamicSegmentListingItem,
} from './api';
import { dynamicSegmentFields } from './fields';
import { isErrorResponse } from '../../ajax';

type Group = 'all' | 'trash';

type PendingAction = {
  action: DynamicSegmentBulkAction;
  selected: DynamicSegmentListingItem[];
} | null;

const DEFAULT_VIEW_BASE: View = {
  type: 'table',
  fields: ['subscribers', 'subscribed', 'updated_at'],
  titleField: 'name',
  showTitle: true,
};

function viewFromQuery(query: ReturnType<typeof getSegmentsQuery>): View {
  return {
    ...DEFAULT_VIEW_BASE,
    perPage: query.limit,
    page: Math.floor(query.offset / query.limit) + 1,
    search: query.search,
    sort: {
      field: query.sort_by,
      direction: query.sort_order === 'asc' ? 'asc' : 'desc',
    },
  };
}

function viewMatchesQuery(
  view: View,
  query: ReturnType<typeof getSegmentsQuery>,
): boolean {
  return (
    (view.perPage ?? 25) === query.limit &&
    (view.page ?? 1) === Math.floor(query.offset / query.limit) + 1 &&
    (view.search ?? '') === query.search &&
    (view.sort?.field ?? 'updated_at') === query.sort_by &&
    (view.sort?.direction ?? 'desc') === query.sort_order
  );
}

function usesNonPageAlignedOffset(
  query: ReturnType<typeof getSegmentsQuery>,
): boolean {
  return query.limit > 0 && query.offset % query.limit !== 0;
}

function actionCount(
  action: DynamicSegmentBulkAction,
  result: DynamicSegmentBulkActionResult,
): number {
  return action === 'delete' ? result.deleted : result.updated;
}

function successMessage(
  action: DynamicSegmentBulkAction,
  count: number,
): string {
  if (action === 'trash') {
    return sprintf(
      /* translators: %d - number of segments */
      _n(
        'Segment moved to trash.',
        '%d segments moved to trash.',
        count,
        'mailpoet',
      ),
      count,
    );
  }
  if (action === 'restore') {
    return sprintf(
      /* translators: %d - number of segments */
      _n('Segment restored.', '%d segments restored.', count, 'mailpoet'),
      count,
    );
  }
  return sprintf(
    /* translators: %d - number of segments */
    _n(
      'Segment permanently deleted.',
      '%d segments permanently deleted.',
      count,
      'mailpoet',
    ),
    count,
  );
}

function confirmCopy(pendingAction: PendingAction): {
  title: string;
  message: JSX.Element | string;
  confirmText: string;
} {
  if (!pendingAction) return { title: '', message: '', confirmText: '' };
  const { action, selected } = pendingAction;
  const list = selected.map(({ name }) => `"${name}"`).join(', ');

  if (action === 'trash') {
    return {
      title: _n(
        'Trash selected segment',
        'Trash selected segments',
        selected.length,
        'mailpoet',
      ),
      message: sprintf(
        // translators: %s is the list of selected segments.
        _n(
          'Are you sure you want to trash the selected segment %s?',
          'Are you sure you want to trash the selected segments %s?',
          selected.length,
          'mailpoet',
        ),
        list,
      ),
      confirmText: __('Trash', 'mailpoet'),
    };
  }

  if (action === 'restore') {
    return {
      title: _n(
        'Restore selected segment',
        'Restore selected segments',
        selected.length,
        'mailpoet',
      ),
      message: sprintf(
        // translators: %s is the list of selected segments.
        _n(
          'Are you sure you want to restore the selected segment %s?',
          'Are you sure you want to restore segments %s?',
          selected.length,
          'mailpoet',
        ),
        list,
      ),
      confirmText: __('Restore', 'mailpoet'),
    };
  }

  return {
    title: _n(
      'Delete selected segment permanently',
      'Delete selected segments permanently',
      selected.length,
      'mailpoet',
    ),
    message: (
      <>
        {sprintf(
          // translators: %s is the list of selected segments.
          _n(
            'Are you sure you want to delete the selected segment %s permanently?',
            'Are you sure you want to delete the selected segments %s permanently?',
            selected.length,
            'mailpoet',
          ),
          list,
        )}{' '}
        <strong>{__('This action can not be reversed.', 'mailpoet')}</strong>
      </>
    ),
    confirmText: __('Delete permanently', 'mailpoet'),
  };
}

export function DynamicSegmentList(): JSX.Element {
  const [initialQuery] = useState(() => getSegmentsQuery());
  const [group, setGroup] = useState<Group>(initialQuery.group as Group);
  const [selection, setSelection] = useState<string[]>([]);
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);
  const [pendingAction, setPendingAction] = useState<PendingAction>(null);
  const legacyOffsetRef = useRef<number | null>(
    usesNonPageAlignedOffset(initialQuery) ? initialQuery.offset : null,
  );
  const latestViewRef = useRef<View>(viewFromQuery(initialQuery));
  const latestGroupRef = useRef<Group>(initialQuery.group as Group);

  const load = useCallback(
    (params: ListingQueryParams) => getDynamicSegments({ ...params, group }),
    [group],
  );

  const {
    view,
    setView,
    items,
    meta,
    groups,
    isLoading,
    error: loadError,
    clearError: clearLoadError,
    refresh,
  } = useDataViewsQuery<DynamicSegmentListingItem>({
    initialView: viewFromQuery(initialQuery),
    load,
    extraParams: (currentView) => {
      if (
        legacyOffsetRef.current !== null &&
        viewMatchesQuery(currentView, {
          ...initialQuery,
          offset: legacyOffsetRef.current,
        })
      ) {
        return {
          page: undefined,
          per_page: undefined,
          offset: legacyOffsetRef.current,
          limit: currentView.perPage ?? initialQuery.limit,
          sort_by: currentView.sort?.field ?? 'updated_at',
          sort_order: currentView.sort?.direction ?? 'desc',
        };
      }
      return {};
    },
  });

  useEffect(() => {
    latestViewRef.current = view;
    latestGroupRef.current = group;
  }, [group, view]);

  useEffect(() => {
    if (
      legacyOffsetRef.current !== null &&
      viewMatchesQuery(view, {
        ...initialQuery,
        offset: legacyOffsetRef.current,
      })
    ) {
      return;
    }
    updateSegmentsQuery({
      group,
      limit: view.perPage ?? 25,
      offset: ((view.page ?? 1) - 1) * (view.perPage ?? 25),
      search: view.search ?? '',
      sort_by: view.sort?.field ?? 'updated_at',
      sort_order: view.sort?.direction ?? 'desc',
    });
  }, [group, initialQuery, view]);

  useEffect(() => {
    const applyHashState = (): void => {
      const nextQuery = getSegmentsQuery();
      const nextGroup = nextQuery.group as Group;
      legacyOffsetRef.current = usesNonPageAlignedOffset(nextQuery)
        ? nextQuery.offset
        : null;
      if (nextGroup !== latestGroupRef.current) {
        setGroup(nextGroup);
      }
      if (!viewMatchesQuery(latestViewRef.current, nextQuery)) {
        setView((currentView) =>
          viewMatchesQuery(currentView, nextQuery)
            ? currentView
            : viewFromQuery(nextQuery),
        );
      }
      setSelection([]);
      setGlobalError(null);
      setGlobalSuccess(null);
      clearLoadError();
    };

    window.addEventListener('hashchange', applyHashState);
    return () => window.removeEventListener('hashchange', applyHashState);
  }, [clearLoadError, setView]);

  const handleViewChange = (nextView: View): void => {
    legacyOffsetRef.current = null;
    setView(nextView);
  };

  const handleBulkAction = useCallback(
    async (
      action: DynamicSegmentBulkAction,
      targets: DynamicSegmentListingItem[],
    ) => {
      if (targets.length === 0) return;
      try {
        const result = await bulkAction(
          action,
          targets.map((segment) => Number(segment.id)),
        );
        const count = actionCount(action, result);
        setSelection([]);
        setGlobalError(
          result.errors.length
            ? result.errors.map((error) => error.message).join('\n')
            : null,
        );
        setGlobalSuccess(count > 0 ? successMessage(action, count) : null);
        refresh();
      } catch (err) {
        const apiError = err as { message?: string };
        setGlobalSuccess(null);
        setGlobalError(
          apiError?.message ||
            __('The bulk action could not be completed.', 'mailpoet'),
        );
      }
    },
    [refresh],
  );

  const handleDuplicate = useCallback(
    async (segment: DynamicSegmentListingItem) => {
      try {
        const response = await MailPoet.Ajax.post({
          api_version: 'v1',
          endpoint: 'dynamic_segments',
          action: 'duplicate',
          data: { id: segment.id },
        });
        const newSegment = response.data as DynamicSegmentListingItem;
        MailPoet.Notice.success(
          sprintf(
            __('Segment "%s" has been duplicated.', 'mailpoet'),
            escapeHTML(newSegment.name),
          ),
        );
        refresh();
      } catch (errorResponse: unknown) {
        if (isErrorResponse(errorResponse)) {
          MailPoet.Notice.showApiErrorNotice(errorResponse);
        }
      }
    },
    [refresh],
  );

  const actions = useMemo<Action<DynamicSegmentListingItem>[]>(
    () => [
      {
        id: 'view_subscribers',
        label: __('View subscribers', 'mailpoet'),
        supportsBulk: false,
        callback: (targets) => {
          const segment = targets[0];
          if (segment) window.location.href = segment.subscribers_url;
        },
      },
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isPrimary: true,
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at && !item.is_plugin_missing,
        callback: (targets) => {
          const segment = targets[0];
          if (segment) {
            window.location.hash = `${ROUTES.EDIT_DYNAMIC_SEGMENT}/${segment.id}`;
          }
        },
      },
      {
        id: 'edit_missing_plugin',
        label: __('Edit unavailable', 'mailpoet'),
        disabled: true,
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at && item.is_plugin_missing,
        callback: () => undefined,
      },
      {
        id: 'duplicate',
        label: __('Duplicate', 'mailpoet'),
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at,
        callback: (targets) => {
          if (targets[0]) void handleDuplicate(targets[0]);
        },
      },
      {
        id: 'trash',
        label: __('Move to trash', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !item.deleted_at,
        callback: (targets) => {
          setPendingAction({ action: 'trash', selected: targets });
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !!item.deleted_at,
        callback: (targets) => {
          setPendingAction({ action: 'restore', selected: targets });
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => !!item.deleted_at,
        callback: (targets) => {
          setPendingAction({ action: 'delete', selected: targets });
        },
      },
    ],
    [handleDuplicate],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const tabs = useMemo(() => {
    const counts: Record<Group, number | null> = { all: null, trash: null };
    (groups ?? []).forEach((entry) => {
      if (entry.name === 'all' || entry.name === 'trash') {
        counts[entry.name] = entry.count;
      }
    });
    const formatTitle = (label: string, count: number | null): string =>
      count === null ? label : `${label} (${count})`;
    return [
      {
        name: 'all',
        title: formatTitle(__('All', 'mailpoet'), counts.all),
        className: 'mailpoet-dataviews-group-all mailpoet-tab-all',
      },
      {
        name: 'trash',
        title: formatTitle(__('Trash', 'mailpoet'), counts.trash),
        className: 'mailpoet-dataviews-group-trash mailpoet-tab-trash',
      },
    ];
  }, [groups]);

  const handleTabSelect = (tabName: string): void => {
    if (tabName !== 'all' && tabName !== 'trash') return;
    if (tabName === group) return;
    legacyOffsetRef.current = null;
    setGroup(tabName);
    setSelection([]);
    setGlobalError(null);
    setGlobalSuccess(null);
    clearLoadError();
    setView({ ...view, page: 1 });
  };

  const copy = confirmCopy(pendingAction);

  return (
    <>
      <TopBarWithBoundary hideScreenOptions />
      <Notices />

      <PageHeader heading={__('Segments', 'mailpoet')}>
        <a
          href={`#${ROUTES.DYNAMIC_SEGMENT_TEMPLATES}`}
          data-automation-id="new-segment"
          className="page-title-action"
        >
          {__('Add new segment', 'mailpoet')}
        </a>
      </PageHeader>

      <div className="mailpoet-segment-subscriber-count">
        <SubscribersInPlan
          subscribersInPlan={MailPoet.subscribersCount}
          subscribersInPlanLimit={MailPoet.subscribersLimit}
        />
        <SubscribersCacheMessage
          cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
        />
      </div>
      <MssAccessNotices />
      {loadError && (
        <Notice status="error" onRemove={clearLoadError}>
          {loadError}
        </Notice>
      )}
      {globalError && (
        <Notice status="error" onRemove={() => setGlobalError(null)}>
          {globalError}
        </Notice>
      )}
      {globalSuccess && (
        <Notice status="success" onRemove={() => setGlobalSuccess(null)}>
          {globalSuccess}
        </Notice>
      )}
      <TabPanel
        key={group}
        className="mailpoet-segments-dataviews__tabs"
        activeClass="is-active"
        tabs={tabs}
        initialTabName={group}
        onSelect={handleTabSelect}
      >
        {() => (
          <div
            className="mailpoet-segments-dataviews mailpoet-segments-listing"
            data-automation-id="dynamic_segments_listing"
          >
            <DataViews<DynamicSegmentListingItem>
              data={items}
              fields={dynamicSegmentFields}
              view={view}
              onChangeView={handleViewChange}
              actions={actions}
              paginationInfo={paginationInfo}
              defaultLayouts={{ table: {} }}
              getItemId={(item) => String(item.id)}
              selection={selection}
              onChangeSelection={setSelection}
              isLoading={isLoading}
              empty={<div>{__('No data to display', 'mailpoet')}</div>}
            >
              <div className="mailpoet-segments-dataviews__toolbar">
                <DataViews.Search label={__('Search', 'mailpoet')} />
              </div>
              <DataViews.Layout />
              <DataViews.Footer />
            </DataViews>
          </div>
        )}
      </TabPanel>
      <ConfirmDialog
        className="mailpoet-confirm-dialog"
        isOpen={!!pendingAction}
        title={copy.title}
        confirmButtonText={copy.confirmText}
        __experimentalHideHeader={false}
        onConfirm={() => {
          if (pendingAction) {
            void handleBulkAction(pendingAction.action, pendingAction.selected);
          }
          setPendingAction(null);
        }}
        onCancel={() => setPendingAction(null)}
      >
        <p>{copy.message}</p>
      </ConfirmDialog>
    </>
  );
}
