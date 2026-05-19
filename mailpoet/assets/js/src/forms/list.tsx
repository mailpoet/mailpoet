import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice, TabPanel } from '@wordpress/components';
import { escapeHTML } from '@wordpress/escape-html';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import { Button } from 'common';
import { withNpsPoll } from 'nps-poll.jsx';
import { useDataViewsQuery, type ListingQueryParams } from 'common/dataviews';
import { FormsHeading, onAddNewForm } from './heading';
import { listFields } from './fields';
import {
  bulkAction,
  getForms,
  type BulkAction,
  type FormListingItem,
} from './api';

type Group = 'all' | 'trash';

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'updated_at', direction: 'desc' },
  fields: ['segments', 'type', 'status', 'updated_at'],
  titleField: 'name',
  showTitle: true,
};

function bulkActionSuccessMessage(action: BulkAction, count: number): string {
  if (action === 'trash') {
    return count === 1
      ? __('1 form was moved to the trash.', 'mailpoet')
      : sprintf(
          /* translators: %d is the number of forms */
          _n(
            '%d form was moved to the trash.',
            '%d forms were moved to the trash.',
            count,
            'mailpoet',
          ),
          count,
        );
  }
  if (action === 'restore') {
    return count === 1
      ? __('1 form has been restored from the trash.', 'mailpoet')
      : sprintf(
          /* translators: %d is the number of forms */
          _n(
            '%d form has been restored from the trash.',
            '%d forms have been restored from the trash.',
            count,
            'mailpoet',
          ),
          count,
        );
  }
  return count === 1
    ? __('1 form was permanently deleted.', 'mailpoet')
    : sprintf(
        /* translators: %d is the number of forms */
        _n(
          '%d form was permanently deleted.',
          '%d forms were permanently deleted.',
          count,
          'mailpoet',
        ),
        count,
      );
}

function FormListComponent(): JSX.Element {
  const [group, setGroup] = useState<Group>('all');
  const [selection, setSelection] = useState<string[]>([]);
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);

  const load = useCallback(
    (params: ListingQueryParams) => getForms({ ...params, group }),
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
  } = useDataViewsQuery<FormListingItem>({
    initialView: DEFAULT_VIEW,
    load,
  });

  // Surface broken-settings forms via the global notice system so admins
  // know to repair them. Each form is warned about at most once per page
  // session to avoid spam when re-fetching.
  const warnedCorruptedIdsRef = useRef<Set<string>>(new Set());
  useEffect(() => {
    items.forEach((form) => {
      if (form.settings !== null) return;
      if (warnedCorruptedIdsRef.current.has(form.id)) return;
      warnedCorruptedIdsRef.current.add(form.id);
      MailPoet.Notice.error(
        __(
          'Form settings of "%1$s" form are corrupted. Please [link]reconfigure the form in the editor[/link].',
          'mailpoet',
        )
          .replace('%1$s', escapeHTML(form.name))
          .replace(
            '[link]',
            `<a class="mailpoet-link" href="admin.php?page=mailpoet-form-editor&id=${parseInt(
              form.id,
              10,
            )}">`,
          )
          .replace('[/link]', '</a>'),
      );
    });
  }, [items]);

  const handleBulkAction = useCallback(
    async (action: BulkAction, targets: FormListingItem[]) => {
      const ids = targets.map((form) => Number(form.id));
      if (ids.length === 0) return;

      if (action === 'delete') {
        const confirmMessage =
          ids.length === 1
            ? __('Delete this form permanently?', 'mailpoet')
            : sprintf(
                /* translators: %d is the number of forms */
                _n(
                  'Delete %d form permanently?',
                  'Delete %d forms permanently?',
                  ids.length,
                  'mailpoet',
                ),
                ids.length,
              );
        // eslint-disable-next-line no-alert
        if (!window.confirm(confirmMessage)) {
          return;
        }
      }

      try {
        const count = await bulkAction(action, ids);
        setSelection([]);
        setGlobalSuccess(bulkActionSuccessMessage(action, count));
        refresh();
      } catch (err) {
        const apiError = err as { message?: string };
        setGlobalError(
          apiError?.message ||
            __('The bulk action could not be completed.', 'mailpoet'),
        );
      }
    },
    [refresh],
  );

  const handleDuplicate = useCallback(
    (form: FormListingItem) => {
      void MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'forms',
        action: 'duplicate',
        data: { id: Number(form.id) },
      })
        .done((response: { data: { name?: string } }) => {
          const formName = response.data.name
            ? response.data.name
            : __('no name', 'mailpoet');
          MailPoet.Notice.success(
            __('Form "%1$s" has been duplicated.', 'mailpoet').replace(
              '%1$s',
              escapeHTML(formName),
            ),
          );
          refresh();
        })
        .fail((response: { errors: unknown[] }) => {
          if (response.errors && response.errors.length > 0) {
            MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
          }
        });
    },
    [refresh],
  );

  const actions = useMemo<Action<FormListingItem>[]>(
    () => [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        icon: 'edit',
        isPrimary: true,
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at,
        callback: (targets) => {
          const form = targets[0];
          if (!form) return;
          window.location.href = `admin.php?page=mailpoet-form-editor&id=${form.id}`;
        },
      },
      {
        id: 'duplicate',
        label: __('Duplicate', 'mailpoet'),
        supportsBulk: false,
        isEligible: (item) => !item.deleted_at,
        callback: (targets) => {
          if (targets[0]) handleDuplicate(targets[0]);
        },
      },
      {
        id: 'trash',
        label: __('Move to trash', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !item.deleted_at,
        callback: (targets) => {
          void handleBulkAction('trash', targets);
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        supportsBulk: true,
        isEligible: (item) => !!item.deleted_at,
        callback: (targets) => {
          void handleBulkAction('restore', targets);
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => !!item.deleted_at,
        callback: (targets) => {
          void handleBulkAction('delete', targets);
        },
      },
    ],
    [handleBulkAction, handleDuplicate],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const handleTabSelect = (tabName: string): void => {
    if (tabName !== 'all' && tabName !== 'trash') return;
    if (tabName === group) return;
    setGroup(tabName);
    setSelection([]);
    // Don't carry "moved to trash" / load-error notices across tabs — they
    // belonged to the previous group's context.
    setGlobalError(null);
    setGlobalSuccess(null);
    clearLoadError();
    setView({ ...view, page: 1 });
  };

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
        className: 'mailpoet-dataviews-group-all',
      },
      {
        name: 'trash',
        title: formatTitle(__('Trash', 'mailpoet'), counts.trash),
        className: 'mailpoet-dataviews-group-trash',
      },
    ];
  }, [groups]);

  const emptyLabel =
    group === 'trash'
      ? __('Trash is empty.', 'mailpoet')
      : __('No forms were found. Why not create a new one?', 'mailpoet');

  return (
    <>
      <FormsHeading />

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
        className="mailpoet-forms-dataviews__tabs"
        activeClass="is-active"
        tabs={tabs}
        initialTabName={group}
        onSelect={handleTabSelect}
      >
        {() => (
          <div
            className="mailpoet-dataviews mailpoet-forms-dataviews"
            data-automation-id="forms_listing"
          >
            <DataViews<FormListingItem>
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
              empty={
                group === 'trash' ? (
                  <div>{emptyLabel}</div>
                ) : (
                  <div className="mailpoet-forms-add-new-row">
                    <p>{emptyLabel}</p>
                    <Button onClick={onAddNewForm} automationId="add_new_form">
                      {__('Add new form', 'mailpoet')}
                    </Button>
                  </div>
                )
              }
            >
              <div className="mailpoet-forms-dataviews__toolbar">
                <DataViews.Search label={__('Search forms', 'mailpoet')} />
              </div>
              <DataViews.Layout />
              <DataViews.Footer />
            </DataViews>
          </div>
        )}
      </TabPanel>
    </>
  );
}

FormListComponent.displayName = 'FormList';

const FormListWithPoll = withNpsPoll(FormListComponent);

export function FormList(): JSX.Element {
  return <FormListWithPoll />;
}
