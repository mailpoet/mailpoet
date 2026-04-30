import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice, TabPanel } from '@wordpress/components';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { BackButton, PageHeader } from 'common/page-header';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { CustomFieldsForm } from './custom-fields-form';
import { listFields } from './fields';
import {
  bulkAction,
  type CustomFieldBulkAction,
  getCustomFields,
  getSubscribersListingUrl,
} from './api';
import type {
  ApiErrorResponse,
  CustomField,
  CustomFieldDateSettings,
  CustomFieldListGroup,
  CustomFieldListMeta,
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
  const [group, setGroup] = useState<Group>('all');
  const [isCreateFormOpen, setIsCreateFormOpen] = useState(false);
  const [editingCustomField, setEditingCustomField] =
    useState<CustomField | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);
  const [view, setView] = useState<View>(DEFAULT_VIEW);
  const [items, setItems] = useState<CustomField[]>([]);
  const [meta, setMeta] = useState<CustomFieldListMeta>({
    count: 0,
    pages: 0,
  });
  const [groups, setGroups] = useState<CustomFieldListGroup[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [selection, setSelection] = useState<string[]>([]);
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);
  const latestRequestIdRef = useRef(0);

  const loadCustomFields = useCallback(async () => {
    const requestId = latestRequestIdRef.current + 1;
    latestRequestIdRef.current = requestId;
    setIsLoading(true);
    try {
      const requestedPage = view.page ?? 1;
      const result = await getCustomFields({
        search: view.search || '',
        orderby: view.sort?.field ?? 'name',
        order: view.sort?.direction ?? 'asc',
        page: requestedPage,
        per_page: view.perPage ?? 20,
        group,
      });

      if (requestId !== latestRequestIdRef.current) {
        return;
      }

      const lastValidPage = Math.max(1, result.meta.pages);
      if (requestedPage > lastValidPage) {
        setView((currentView) => ({
          ...currentView,
          page: lastValidPage,
        }));
        return;
      }

      setItems(result.items);
      setMeta(result.meta);
      setGroups(result.groups);
    } catch (err) {
      if (requestId !== latestRequestIdRef.current) {
        return;
      }
      const apiError = err as ApiErrorResponse;
      setGlobalError(
        apiError?.message || __('Failed to load custom fields.', 'mailpoet'),
      );
    } finally {
      if (requestId === latestRequestIdRef.current) {
        setIsLoading(false);
      }
    }
  }, [group, view.search, view.sort, view.page, view.perPage]);

  useEffect(() => {
    void loadCustomFields();
  }, [loadCustomFields, refreshToken]);

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
        void loadCustomFields();
      } catch (err) {
        const apiError = err as ApiErrorResponse;
        setGlobalError(
          apiError?.message ||
            __('The bulk action could not be completed.', 'mailpoet'),
        );
      }
    },
    [loadCustomFields],
  );

  const actions = useMemo<Action<CustomField>[]>(
    () => [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isEligible: (item) => !item.deleted_at,
        callback: (selected) => {
          const [customField] = selected;
          if (customField) {
            setEditingCustomField(customField);
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
    [handleBulkAction],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const groupCounts = useMemo(() => {
    const counts: Record<Group, number | null> = { all: null, trash: null };
    groups.forEach((entry) => {
      counts[entry.name] = entry.count;
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
    setRefreshToken((current) => current + 1);
  };

  const handleEditSuccess = (customField: CustomField): void => {
    setEditingCustomField(null);
    setGlobalSuccess(
      sprintf(__('Custom field "%s" updated.', 'mailpoet'), customField.name),
    );
    setRefreshToken((current) => current + 1);
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
          <div className="mailpoet-custom-fields-dataviews">
            <DataViews<CustomField>
              data={items}
              fields={listFields}
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
              <div className="mailpoet-custom-fields-dataviews__toolbar">
                <DataViews.Search
                  label={__('Search custom fields', 'mailpoet')}
                />
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
              <DataViews.Footer />
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
