import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { DataViews, View, Action } from '@wordpress/dataviews';
import { getDataViewsPreference } from 'common/dataviews';
import { BackButton, PageHeader } from 'common/page-header';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { listFields } from './fields';
import { TagsForm } from './tags-form';
import {
  bulkDeleteTags,
  deleteTag,
  getSubscribersListingUrl,
  getTags,
} from './api';
import type { ApiErrorResponse, Tag, TagListMeta } from './types';

type FormState =
  | { kind: 'closed' }
  | { kind: 'create' }
  | { kind: 'edit'; tag: Tag };

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: 20,
  page: 1,
  sort: { field: 'name', direction: 'asc' },
  fields: ['description', 'subscribers_count', 'created_at'],
  titleField: 'name',
  showTitle: true,
};

export function TagsPage() {
  const [view, setView] = useState<View>(() =>
    getDataViewsPreference('tags', DEFAULT_VIEW, listFields),
  );
  const [items, setItems] = useState<Tag[]>([]);
  const [meta, setMeta] = useState<TagListMeta>({ count: 0, pages: 0 });
  const [isLoading, setIsLoading] = useState(false);
  const [selection, setSelection] = useState<string[]>([]);
  const [formState, setFormState] = useState<FormState>({ kind: 'closed' });
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);
  const latestRequestIdRef = useRef(0);

  const loadTags = useCallback(async () => {
    const requestId = latestRequestIdRef.current + 1;
    latestRequestIdRef.current = requestId;
    setIsLoading(true);
    try {
      const requestedPage = view.page ?? 1;
      const result = await getTags({
        search: view.search || '',
        orderby: view.sort?.field ?? 'name',
        order: view.sort?.direction ?? 'asc',
        page: requestedPage,
        per_page: view.perPage ?? 20,
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
    } catch (err) {
      if (requestId !== latestRequestIdRef.current) {
        return;
      }
      const apiError = err as ApiErrorResponse;
      setGlobalError(
        apiError?.message || __('Failed to load tags.', 'mailpoet'),
      );
    } finally {
      if (requestId === latestRequestIdRef.current) {
        setIsLoading(false);
      }
    }
  }, [view.search, view.sort, view.page, view.perPage]);

  useEffect(() => {
    void loadTags();
  }, [loadTags]);

  const handleFormSuccess = (tag: Tag) => {
    setFormState({ kind: 'closed' });
    setGlobalSuccess(
      formState.kind === 'edit'
        ? sprintf(__('Tag "%s" updated.', 'mailpoet'), tag.name)
        : sprintf(__('Tag "%s" created.', 'mailpoet'), tag.name),
    );
    void loadTags();
  };

  const handleDelete = useCallback(
    async (ids: number[]) => {
      const confirmMessage =
        ids.length === 1
          ? __(
              'Delete this tag? It will be removed from all subscribers.',
              'mailpoet',
            )
          : sprintf(
              /* translators: %d is the number of tags */
              _n(
                'Delete %d tag? It will be removed from all subscribers.',
                'Delete %d tags? They will be removed from all subscribers.',
                ids.length,
                'mailpoet',
              ),
              ids.length,
            );
      // eslint-disable-next-line no-alert
      if (!window.confirm(confirmMessage)) {
        return;
      }
      try {
        if (ids.length === 1) {
          await deleteTag(ids[0]);
        } else {
          await bulkDeleteTags(ids);
        }
        setSelection([]);
        setGlobalSuccess(
          ids.length === 1
            ? __('Tag deleted.', 'mailpoet')
            : sprintf(
                /* translators: %d is the number of tags */
                _n(
                  '%d tag deleted.',
                  '%d tags deleted.',
                  ids.length,
                  'mailpoet',
                ),
                ids.length,
              ),
        );
        void loadTags();
      } catch (err) {
        const apiError = err as ApiErrorResponse;
        setGlobalError(
          apiError?.message || __('Failed to delete tags.', 'mailpoet'),
        );
      }
    },
    [loadTags],
  );

  const actions = useMemo<Action<Tag>[]>(
    () => [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isPrimary: true,
        icon: 'edit',
        supportsBulk: false,
        callback: (selected) => {
          if (selected[0]) {
            setFormState({ kind: 'edit', tag: selected[0] });
          }
        },
      },
      {
        id: 'delete',
        label: __('Delete', 'mailpoet'),
        isPrimary: false,
        supportsBulk: true,
        callback: (selected) => {
          void handleDelete(selected.map((tag) => tag.id));
        },
      },
    ],
    [handleDelete],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  return (
    <>
      <TopBarWithBoundary />
      <PageHeader
        heading={__('Tags', 'mailpoet')}
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
          onClick={() => setFormState({ kind: 'create' })}
          data-automation-id="add-new-tag-button"
        >
          {__('Add new tag', 'mailpoet')}
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

      <div className="mailpoet-tags-dataviews">
        <DataViews<Tag>
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
          empty={<div>{__('No tags yet.', 'mailpoet')}</div>}
        >
          <div className="mailpoet-tags-dataviews__toolbar">
            <DataViews.Search label={__('Search tags', 'mailpoet')} />
          </div>
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>

      {formState.kind !== 'closed' && (
        <TagsForm
          initialTag={formState.kind === 'edit' ? formState.tag : undefined}
          onClose={() => setFormState({ kind: 'closed' })}
          onSuccess={handleFormSuccess}
        />
      )}
    </>
  );
}
