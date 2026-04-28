import { useCallback, useEffect, useMemo, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
  __experimentalConfirmDialog as ConfirmDialog,
  Notice,
  TabPanel,
} from '@wordpress/components';
import { escapeHTML } from '@wordpress/escape-html';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { useDataViewsQuery, type ListingQueryParams } from 'common/dataviews';
import { ListHeading } from 'segments/static/heading';
import {
  bulkAction,
  getSegments,
  type SegmentBulkAction,
  type SegmentBulkActionResult,
  type SegmentListingItem,
} from './api';
import { segmentFields } from './fields';

type Group = 'all' | 'trash';

type PendingAction = {
  action: SegmentBulkAction;
  targets: SegmentListingItem[];
  params?: Partial<ListingQueryParams> & { select_all?: boolean };
  count: number;
} | null;

const listingPerPage = Number(window.mailpoet_listing_per_page);

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: listingPerPage,
  page: 1,
  sort: { field: 'name', direction: 'asc' },
  fields: [
    'description',
    ...(MailPoet.trackingConfig.emailTrackingEnabled
      ? ['average_engagement_score']
      : []),
    'subscribed',
    'unconfirmed',
    'unsubscribed',
    'inactive',
    'bounced',
    'created_at',
  ],
  titleField: 'name',
  showTitle: true,
};

function isWPUsersSegment(segment: SegmentListingItem): boolean {
  return segment.type === 'wp_users';
}

function isWooCommerceCustomersSegment(segment: SegmentListingItem): boolean {
  return segment.type === 'woocommerce_users';
}

function isSpecialSegment(segment: SegmentListingItem): boolean {
  return isWPUsersSegment(segment) || isWooCommerceCustomersSegment(segment);
}

function parseHash(): Partial<{
  group: Group;
  page: number;
  perPage: number;
  orderby: string;
  order: 'asc' | 'desc';
}> {
  return window.location.hash
    .split('/')
    .map((part) => part.replace(/\]$/, '').split('['))
    .reduce((params, [key, value]) => {
      if (!value) return params;
      if (key === 'group' && (value === 'all' || value === 'trash')) {
        return { ...params, group: value };
      }
      if ((key === 'page' || key === 'paged') && Number(value) > 0) {
        return { ...params, page: Number(value) };
      }
      if ((key === 'per_page' || key === 'limit') && Number(value) > 0) {
        return { ...params, perPage: Number(value) };
      }
      if (key === 'sort_by' || key === 'orderby') {
        return { ...params, orderby: value };
      }
      if (
        (key === 'sort_order' || key === 'order') &&
        (value === 'asc' || value === 'desc')
      ) {
        return { ...params, order: value };
      }
      return params;
    }, {});
}

function updateHash(group: Group, view: View): void {
  const entries: Array<[string, string | number | undefined]> = [
    ['group', group],
    ['page', view.page && view.page !== 1 ? view.page : undefined],
    [
      'limit',
      view.perPage && view.perPage !== listingPerPage
        ? view.perPage
        : undefined,
    ],
    [
      'sort_by',
      view.sort?.field && view.sort.field !== 'name'
        ? view.sort.field
        : undefined,
    ],
    [
      'sort_order',
      view.sort?.direction && view.sort.direction !== 'asc'
        ? view.sort.direction
        : undefined,
    ],
  ];
  const path = entries.reduce(
    (hash, [key, value]) => (value ? `${hash}/${key}[${value}]` : hash),
    '',
  );
  window.history.replaceState(null, '', `#/lists${path}`);
}

function countForAction(
  action: SegmentBulkAction,
  result: SegmentBulkActionResult,
): number {
  if (action === 'delete' || action === 'empty_trash') return result.deleted;
  return result.updated;
}

function successMessage(
  action: SegmentBulkAction,
  count: number,
  isWPUsers = false,
): string {
  if (action === 'trash') {
    const label = isWPUsers
      ? __('1 list was moved to the trash.', 'mailpoet')
      : sprintf(
          /* translators: %d is the number of lists. */
          _n(
            '%d list was moved to the trash.',
            '%d lists were moved to the trash.',
            count,
            'mailpoet',
          ),
          count,
        );
    return `${label} ${__(
      'Note that deleting a list does not delete its subscribers.',
      'mailpoet',
    )}`;
  }
  if (action === 'restore') {
    return count === 1
      ? __('1 list has been restored from the Trash.', 'mailpoet')
      : sprintf(
          /* translators: %d is the number of lists. */
          _n(
            '%d list has been restored from the Trash.',
            '%d lists have been restored from the Trash.',
            count,
            'mailpoet',
          ),
          count,
        );
  }
  const label = sprintf(
    /* translators: %d is the number of lists. */
    _n(
      '%d list was permanently deleted.',
      '%d lists were permanently deleted.',
      count,
      'mailpoet',
    ),
    count,
  );
  return `${label} ${__(
    'Note that deleting a list does not delete its subscribers.',
    'mailpoet',
  )}`;
}

function confirmCopy(pendingAction: PendingAction): {
  title: string;
  message: JSX.Element | string;
  confirmText: string;
} {
  if (!pendingAction) return { title: '', message: '', confirmText: '' };
  const { action, count } = pendingAction;

  if (action === 'empty_trash') {
    return {
      title: __('Empty Trash', 'mailpoet'),
      message: (
        <>
          {sprintf(
            /* translators: %d is the number of lists. */
            _n(
              'Are you sure you want to permanently delete %d list from the Trash?',
              'Are you sure you want to permanently delete %d lists from the Trash?',
              count,
              'mailpoet',
            ),
            count,
          )}{' '}
          <strong>{__('This action can not be reversed.', 'mailpoet')}</strong>
        </>
      ),
      confirmText: __('Empty Trash', 'mailpoet'),
    };
  }

  return {
    title: _n(
      'Delete selected list permanently',
      'Delete selected lists permanently',
      count,
      'mailpoet',
    ),
    message: (
      <>
        {sprintf(
          /* translators: %d is the number of lists. */
          _n(
            'Are you sure you want to permanently delete %d selected list?',
            'Are you sure you want to permanently delete %d selected lists?',
            count,
            'mailpoet',
          ),
          count,
        )}{' '}
        <strong>{__('This action can not be reversed.', 'mailpoet')}</strong>
      </>
    ),
    confirmText: __('Delete permanently', 'mailpoet'),
  };
}

function SegmentListComponent(): JSX.Element {
  const hashState = parseHash();
  const [group, setGroup] = useState<Group>(hashState.group ?? 'all');
  const [selection, setSelection] = useState<string[]>([]);
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);
  const [pendingAction, setPendingAction] = useState<PendingAction>(null);
  const [initialView] = useState<View>(() => ({
    ...DEFAULT_VIEW,
    page: hashState.page ?? DEFAULT_VIEW.page,
    perPage: hashState.perPage ?? DEFAULT_VIEW.perPage,
    sort: {
      field: hashState.orderby ?? DEFAULT_VIEW.sort?.field ?? 'name',
      direction: hashState.order ?? DEFAULT_VIEW.sort?.direction ?? 'asc',
    },
  }));

  const load = useCallback(
    (params: ListingQueryParams) => getSegments({ ...params, group }),
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
  } = useDataViewsQuery<SegmentListingItem>({
    initialView,
    load,
  });

  useEffect(() => {
    updateHash(group, view);
  }, [group, view]);

  const handleBulkAction = useCallback(
    async (
      action: SegmentBulkAction,
      targets: SegmentListingItem[],
      params?: Partial<ListingQueryParams> & { select_all?: boolean },
    ) => {
      const ids = targets.map((segment) => Number(segment.id));
      if (!params?.select_all && action !== 'empty_trash' && ids.length === 0) {
        return;
      }

      try {
        const result = await bulkAction(action, ids, params);
        const count = countForAction(action, result);
        setSelection([]);
        setGlobalError(
          result.errors.length
            ? result.errors.map((error) => error.message).join('\n')
            : null,
        );
        setGlobalSuccess(
          count > 0
            ? successMessage(action, count, targets.some(isWPUsersSegment))
            : null,
        );
        if (
          (action === 'restore' ||
            action === 'delete' ||
            action === 'empty_trash') &&
          group === 'trash'
        ) {
          setGroup('all');
          setView({ ...view, page: 1 });
        }
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
    [group, refresh, setView, view],
  );

  const handleDuplicate = useCallback(
    (segment: SegmentListingItem) => {
      void MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'duplicate',
        data: { id: segment.id },
      })
        .done((response: { data: { name: string } }) => {
          MailPoet.Notice.success(
            MailPoet.I18n.t('listDuplicated').replace(
              '%1$s',
              escapeHTML(response.data.name),
            ),
          );
          refresh();
        })
        .fail((response) => {
          MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
        });
    },
    [refresh],
  );

  const handleSynchronize = useCallback(
    (segment: SegmentListingItem) => {
      MailPoet.Modal.loading(true);
      void MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'synchronize',
        data: { type: segment.type },
      })
        .done(() => {
          const message =
            segment.type === 'woocommerce_users'
              ? MailPoet.I18n.t('listSynchronizationWasScheduled').replace(
                  '%1$s',
                  segment.name,
                )
              : MailPoet.I18n.t('listSynchronized').replace(
                  '%1$s',
                  segment.name,
                );
          MailPoet.Modal.loading(false);
          MailPoet.Notice.success(message);
          refresh();
        })
        .fail((response) => {
          MailPoet.Modal.loading(false);
          if (response.errors.length > 0) {
            MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
          }
        });
    },
    [refresh],
  );

  const actions = useMemo<Action<SegmentListingItem>[]>(
    () => [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isPrimary: true,
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at && !isSpecialSegment(item),
        callback: (targets) => {
          const segment = targets[0];
          if (segment) window.location.hash = `/edit/${segment.id}`;
        },
      },
      {
        id: 'duplicate',
        label: MailPoet.I18n.t('duplicate'),
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at && !isSpecialSegment(item),
        callback: (targets) => {
          if (targets[0]) handleDuplicate(targets[0]);
        },
      },
      {
        id: 'read_more',
        label: MailPoet.I18n.t('readMore'),
        supportsBulk: false,
        isEligible: (item) => isWPUsersSegment(item),
        callback: () => {
          window.open(
            'https://kb.mailpoet.com/article/133-the-wordpress-users-list',
            '_blank',
            'noopener,noreferrer',
          );
        },
      },
      {
        id: 'synchronize',
        label: MailPoet.I18n.t('forceSync'),
        supportsBulk: false,
        isEligible: (item) =>
          isWPUsersSegment(item) || isWooCommerceCustomersSegment(item),
        callback: (targets) => {
          if (targets[0]) handleSynchronize(targets[0]);
        },
      },
      {
        id: 'view_subscribers',
        label: MailPoet.I18n.t('viewSubscribers'),
        supportsBulk: false,
        callback: (targets) => {
          const segment = targets[0];
          if (segment) window.location.href = segment.subscribers_url;
        },
      },
      {
        id: 'trash_wp_users',
        label: __('Trash and disable', 'mailpoet'),
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at && isWPUsersSegment(item),
        callback: (targets) => {
          void handleBulkAction('trash', targets);
        },
      },
      {
        id: 'trash',
        label: MailPoet.I18n.t('moveToTrash'),
        supportsBulk: true,
        isEligible: (item) =>
          !item.deleted_at &&
          !isWPUsersSegment(item) &&
          !isWooCommerceCustomersSegment(item),
        callback: (targets) => {
          void handleBulkAction('trash', targets);
        },
      },
      {
        id: 'restore_wp_users',
        label: __('Restore and enable', 'mailpoet'),
        supportsBulk: false,
        isEligible: (item) => !!item.deleted_at && isWPUsersSegment(item),
        callback: (targets) => {
          void handleBulkAction('restore', targets);
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !!item.deleted_at && !isWPUsersSegment(item),
        callback: (targets) => {
          void handleBulkAction('restore', targets);
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => !!item.deleted_at && !isSpecialSegment(item),
        callback: (targets) => {
          setPendingAction({
            action: 'delete',
            targets,
            count: targets.length,
          });
        },
      },
    ],
    [handleBulkAction, handleDuplicate, handleSynchronize],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const groupCounts = useMemo(() => {
    const counts: Record<Group, number | null> = { all: null, trash: null };
    (groups ?? []).forEach((entry) => {
      if (entry.name === 'all' || entry.name === 'trash') {
        counts[entry.name] = entry.count;
      }
    });
    return counts;
  }, [groups]);

  const tabs = useMemo(() => {
    const formatTitle = (label: string, count: number | null): string =>
      count === null ? label : `${label} (${count})`;
    return [
      {
        name: 'all',
        title: formatTitle(__('All', 'mailpoet'), groupCounts.all),
        className: 'mailpoet-dataviews-group-all',
      },
      ...(group === 'trash' || (groupCounts.trash ?? 0) > 0
        ? [
            {
              name: 'trash',
              title: formatTitle(__('Trash', 'mailpoet'), groupCounts.trash),
              className: 'mailpoet-dataviews-group-trash',
            },
          ]
        : []),
    ];
  }, [group, groupCounts]);

  const handleTabSelect = (tabName: string): void => {
    if (tabName !== 'all' && tabName !== 'trash') return;
    if (tabName === group) return;
    setGroup(tabName);
    setSelection([]);
    setGlobalError(null);
    setGlobalSuccess(null);
    clearLoadError();
    setView({ ...view, page: 1 });
  };

  const emptyLabel =
    group === 'trash'
      ? __('Trash is empty.', 'mailpoet')
      : __('No lists found.', 'mailpoet');
  const pendingActionCopy = confirmCopy(pendingAction);

  return (
    <>
      <HideScreenOptions />
      <ListHeading />
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
            data-automation-id="segments_listing"
          >
            <DataViews<SegmentListingItem>
              data={items}
              fields={segmentFields}
              view={view}
              onChangeView={setView}
              actions={actions}
              paginationInfo={paginationInfo}
              defaultLayouts={{ table: {} }}
              getItemId={(item) => String(item.id)}
              selection={selection}
              onChangeSelection={setSelection}
              isLoading={isLoading}
              empty={<div>{emptyLabel}</div>}
            >
              {group === 'trash' && (groupCounts.trash ?? 0) > 0 && (
                <div className="mailpoet-segments-dataviews__toolbar">
                  <button
                    type="button"
                    className="button"
                    data-automation-id="empty_trash"
                    onClick={() => {
                      setPendingAction({
                        action: 'empty_trash',
                        targets: [],
                        count: groupCounts.trash ?? 0,
                        params: {
                          select_all: true,
                          group: 'trash',
                          page: view.page ?? 1,
                          per_page: view.perPage ?? listingPerPage,
                          orderby: view.sort?.field,
                          order: view.sort?.direction,
                        },
                      });
                    }}
                  >
                    {__('Empty Trash', 'mailpoet')}
                  </button>
                </div>
              )}
              <DataViews.Layout />
              <DataViews.Footer />
            </DataViews>
          </div>
        )}
      </TabPanel>
      <ConfirmDialog
        className="mailpoet-confirm-dialog"
        isOpen={!!pendingAction}
        title={pendingActionCopy.title}
        confirmButtonText={pendingActionCopy.confirmText}
        __experimentalHideHeader={false}
        onConfirm={() => {
          if (pendingAction) {
            void handleBulkAction(
              pendingAction.action,
              pendingAction.targets,
              pendingAction.params,
            );
          }
          setPendingAction(null);
        }}
        onCancel={() => setPendingAction(null)}
      >
        <p>{pendingActionCopy.message}</p>
      </ConfirmDialog>
    </>
  );
}

export function SegmentList(): JSX.Element {
  return <SegmentListComponent />;
}
