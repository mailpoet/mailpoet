import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice, TabPanel } from '@wordpress/components';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { BackButton, PageHeader } from 'common/page-header';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import {
  getDataViewsPreference,
  type LoadListing,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  DataViewsFooter,
} from 'common/dataviews';
import type { ListingGroup } from 'common/dataviews/types';
import { CustomFieldsForm } from './custom-fields-form';
import { listFields } from './fields';
import {
  bulkAction,
  type CustomFieldBulkAction,
  duplicateCustomField,
  getCustomFields,
  getSubscribersListingUrl,
} from './api';
import {
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from './filters';
import { buildCustomFieldsUrl, parseCustomFieldsUrlState } from './url-state';
import type {
  ApiErrorResponse,
  CustomField,
  CustomFieldDateSettings,
} from './types';

type Group = 'all' | 'trash';

declare global {
  interface Window {
    mailpoet_custom_fields_date_types?: CustomFieldDateSettings['dateTypes'];
    mailpoet_custom_fields_date_formats?: CustomFieldDateSettings['dateFormats'];
  }
}

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'name', direction: 'asc' },
  fields: [
    'label',
    'type',
    'required',
    'subscribers_count',
    'forms_count',
    'dynamic_segments_count',
    'created_at',
  ],
  titleField: 'name',
  showTitle: true,
};

function bulkActionSuccessMessage(
  action: CustomFieldBulkAction,
  count: number,
): string {
  if (action === 'trash') {
    return count === 1
      ? __('1 custom field was moved to the trash.', 'mailpoet')
      : sprintf(
          /* translators: %d is the number of custom fields */
          _n(
            '%d custom field was moved to the trash.',
            '%d custom fields were moved to the trash.',
            count,
            'mailpoet',
          ),
          count,
        );
  }
  if (action === 'restore') {
    return count === 1
      ? __('1 custom field has been restored from the trash.', 'mailpoet')
      : sprintf(
          /* translators: %d is the number of custom fields */
          _n(
            '%d custom field has been restored from the trash.',
            '%d custom fields have been restored from the trash.',
            count,
            'mailpoet',
          ),
          count,
        );
  }
  return count === 1
    ? __('1 custom field was permanently deleted.', 'mailpoet')
    : sprintf(
        /* translators: %d is the number of custom fields */
        _n(
          '%d custom field was permanently deleted.',
          '%d custom fields were permanently deleted.',
          count,
          'mailpoet',
        ),
        count,
      );
}

export function CustomFieldsPage() {
  const initialUrlState = useMemo(
    () => parseCustomFieldsUrlState(window.location.href),
    [],
  );
  const [group, setGroup] = useState<Group>(initialUrlState.group);
  const [isCreateFormOpen, setIsCreateFormOpen] = useState(false);
  const [editingCustomField, setEditingCustomField] =
    useState<CustomField | null>(null);
  const [selection, setSelection] = useState<string[]>([]);
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);
  const [initialView] = useState<View>(() => {
    const preferredView = getDataViewsPreference(
      'custom-fields',
      DEFAULT_VIEW,
      listFields,
    );
    return {
      ...preferredView,
      page: initialUrlState.page,
      perPage: initialUrlState.perPage ?? preferredView.perPage,
      search: initialUrlState.search,
      filters: requestFilterToViewFilters(initialUrlState.filter),
    };
  });
  const didMountRef = useRef(false);

  const load = useCallback<LoadListing<CustomField>>(
    async (params) => {
      const result = await getCustomFields({
        search: typeof params.search === 'string' ? params.search : '',
        orderby: params.orderby ?? 'name',
        order: params.order ?? 'asc',
        page: params.page,
        per_page: params.per_page,
        group,
        filter: params.filter,
      });
      return {
        items: result.items,
        meta: result.meta,
        groups: result.groups as ListingGroup[],
      };
    },
    [group],
  );

  const extraParams = useCallback(
    (currentView: View) => ({
      filter: viewFiltersToRequestFilter(currentView.filters),
    }),
    [],
  );

  const {
    view,
    setView,
    items,
    meta,
    groups,
    isLoading,
    error: loadError,
    onChangeView,
    refresh,
    clearError: clearLoadError,
  } = useDataViewsQuery<CustomField>({
    initialView,
    load,
    extraParams,
  });

  const onChangeViewWithFilterReset = useCallback(
    (nextView: View): void => {
      const filtersChanged =
        JSON.stringify(nextView.filters ?? []) !==
        JSON.stringify(view.filters ?? []);
      onChangeView(filtersChanged ? { ...nextView, page: 1 } : nextView);
    },
    [onChangeView, view.filters],
  );

  const handleViewChange = usePersistedDataViewsPreference(
    'custom-fields',
    view,
    onChangeViewWithFilterReset,
  );

  useEffect(() => {
    if (!didMountRef.current) {
      didMountRef.current = true;
      return;
    }
    const nextUrl = buildCustomFieldsUrl(
      window.location.href,
      view,
      group,
      viewFiltersToRequestFilter(view.filters),
    );
    window.history.replaceState({}, '', nextUrl);
  }, [view, group]);

  const handleBulkAction = useCallback(
    async (
      action: CustomFieldBulkAction,
      ids: number[],
      count = ids.length,
    ) => {
      if (count === 0) return;

      if (action === 'delete' || action === 'empty_trash') {
        const confirmMessage =
          action === 'empty_trash'
            ? sprintf(
                /* translators: %d is the number of custom fields */
                _n(
                  'Permanently delete %d custom field from the trash? It will be removed from subscribers, forms, and dynamic segments.',
                  'Permanently delete %d custom fields from the trash? They will be removed from subscribers, forms, and dynamic segments.',
                  count,
                  'mailpoet',
                ),
                count,
              )
            : sprintf(
                /* translators: %d is the number of custom fields */
                _n(
                  'Permanently delete %d custom field? It will be removed from subscribers, forms, and dynamic segments.',
                  'Permanently delete %d custom fields? They will be removed from subscribers, forms, and dynamic segments.',
                  count,
                  'mailpoet',
                ),
                count,
              );
        // eslint-disable-next-line no-alert
        if (!window.confirm(confirmMessage)) {
          return;
        }
      }
      try {
        const updatedCount = await bulkAction(action, ids);
        setSelection([]);
        setGlobalSuccess(bulkActionSuccessMessage(action, updatedCount));
        refresh();
      } catch (err) {
        const apiError = err as ApiErrorResponse;
        setGlobalError(
          apiError?.message ||
            __('The bulk action could not be completed.', 'mailpoet'),
        );
      }
    },
    [refresh],
  );

  const handleDuplicate = useCallback(
    async (customField: CustomField) => {
      try {
        const duplicate = await duplicateCustomField(customField.id);
        setSelection([]);
        setGroup('all');
        setGlobalSuccess(
          sprintf(
            __('Custom field "%s" duplicated.', 'mailpoet'),
            duplicate.name,
          ),
        );
        refresh();
      } catch (err) {
        const apiError = err as ApiErrorResponse;
        setGlobalError(
          apiError?.message ||
            __('The custom field could not be duplicated.', 'mailpoet'),
        );
      }
    },
    [refresh],
  );

  const actions = useMemo<Action<CustomField>[]>(
    () => [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isPrimary: true,
        icon: 'edit',
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at,
        callback: (selected) => {
          const [customField] = selected;
          if (customField) {
            setEditingCustomField(customField);
          }
        },
      },
      {
        id: 'duplicate',
        label: __('Duplicate', 'mailpoet'),
        isEligible: (item) => !item.deleted_at,
        callback: (selected) => {
          const [customField] = selected;
          if (customField) {
            void handleDuplicate(customField);
          }
        },
      },
      {
        id: 'trash',
        label: __('Move to trash', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !item.deleted_at,
        callback: (selected) => {
          void handleBulkAction(
            'trash',
            selected.map((customField) => customField.id),
          );
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !!item.deleted_at,
        callback: (selected) => {
          void handleBulkAction(
            'restore',
            selected.map((customField) => customField.id),
          );
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => !!item.deleted_at,
        callback: (selected) => {
          void handleBulkAction(
            'delete',
            selected.map((customField) => customField.id),
          );
        },
      },
    ],
    [handleBulkAction, handleDuplicate],
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

  const tabs = useMemo(
    () =>
      (
        [
          { name: 'all', label: __('All', 'mailpoet') },
          { name: 'trash', label: __('Trash', 'mailpoet') },
        ] as Array<{ name: Group; label: string }>
      ).map((tab) => ({
        name: tab.name,
        title:
          groupCounts[tab.name] === null
            ? tab.label
            : `${tab.label} (${groupCounts[tab.name]})`,
        className: `mailpoet-dataviews-group-${tab.name}`,
      })),
    [groupCounts],
  );

  const handleTabSelect = (tabName: string): void => {
    if (tabName !== 'all' && tabName !== 'trash') return;
    if (tabName === group) return;
    setGroup(tabName);
    setSelection([]);
    setGlobalError(null);
    setGlobalSuccess(null);
    setView((currentView) => ({ ...currentView, page: 1 }));
  };

  const emptyLabel =
    group === 'trash'
      ? __('Trash is empty.', 'mailpoet')
      : __('No custom fields yet.', 'mailpoet');

  const dateSettings = useMemo<CustomFieldDateSettings>(
    () => ({
      dateTypes: window.mailpoet_custom_fields_date_types ?? [],
      dateFormats: window.mailpoet_custom_fields_date_formats ?? {},
    }),
    [],
  );

  const handleCreateSuccess = (customField: CustomField): void => {
    setIsCreateFormOpen(false);
    setGlobalSuccess(
      sprintf(__('Custom field "%s" created.', 'mailpoet'), customField.name),
    );
    setGroup('all');
    setSelection([]);
    setView((currentView) => ({ ...currentView, page: 1 }));
    refresh();
  };

  const handleEditSuccess = (customField: CustomField): void => {
    setEditingCustomField(null);
    setGlobalSuccess(
      sprintf(__('Custom field "%s" updated.', 'mailpoet'), customField.name),
    );
    refresh();
  };

  return (
    <>
      <TopBarWithBoundary />
      <PageHeader
        heading={__('Custom Fields', 'mailpoet')}
        headingPrefix={
          <BackButton
            href={getSubscribersListingUrl()}
            label={__('Subscribers', 'mailpoet')}
            aria-label={__('Back to subscribers list', 'mailpoet')}
          />
        }
      >
        <button
          type="button"
          className="page-title-action"
          onClick={() => setIsCreateFormOpen(true)}
          data-automation-id="add-new-custom-field-button"
        >
          {__('Add new custom field', 'mailpoet')}
        </button>
      </PageHeader>

      {loadError && (
        <Notice status="error" onRemove={clearLoadError}>
          {loadError === 'Failed to load data.'
            ? __('Failed to load custom fields.', 'mailpoet')
            : loadError}
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
        className="mailpoet-custom-fields-dataviews__tabs"
        activeClass="is-active"
        tabs={tabs}
        initialTabName={group}
        onSelect={handleTabSelect}
      >
        {() => (
          <div className="mailpoet-dataviews mailpoet-custom-fields-dataviews">
            <DataViews<CustomField>
              data={items}
              fields={listFields}
              view={view}
              onChangeView={handleViewChange}
              actions={actions}
              paginationInfo={paginationInfo}
              defaultLayouts={{ table: {} }}
              getItemId={(item) => String(item.id)}
              selection={selection}
              onChangeSelection={setSelection}
              isLoading={isLoading}
              empty={<div>{emptyLabel}</div>}
            >
              <div className="mailpoet-custom-fields-dataviews__toolbar">
                <DataViews.Search
                  label={__('Search custom fields', 'mailpoet')}
                />
                <DataViews.FiltersToggle />
                <div className="mailpoet-dataviews__toolbar-end">
                  <DataViews.ViewConfig />
                </div>
              </div>
              <div className="mailpoet-dataviews__filters">
                <DataViews.Filters />
              </div>
              {group === 'trash' && (groupCounts.trash ?? 0) > 0 && (
                <div className="mailpoet-custom-fields-dataviews__toolbar">
                  <button
                    type="button"
                    className="button"
                    data-automation-id="empty_trash_custom_fields"
                    onClick={() => {
                      void handleBulkAction(
                        'empty_trash',
                        [],
                        groupCounts.trash ?? 0,
                      );
                    }}
                  >
                    {__('Empty Trash', 'mailpoet')}
                  </button>
                </div>
              )}
              <DataViews.Layout />
              <DataViewsFooter
                view={view}
                onChangeView={handleViewChange}
                paginationInfo={paginationInfo}
                isLoading={isLoading}
                hasData={items.length > 0}
              />
            </DataViews>
          </div>
        )}
      </TabPanel>

      {isCreateFormOpen && (
        <CustomFieldsForm
          dateSettings={dateSettings}
          onClose={() => setIsCreateFormOpen(false)}
          onSuccess={handleCreateSuccess}
        />
      )}

      {editingCustomField && (
        <CustomFieldsForm
          customField={editingCustomField}
          dateSettings={dateSettings}
          onClose={() => setEditingCustomField(null)}
          onSuccess={handleEditSuccess}
        />
      )}
    </>
  );
}
