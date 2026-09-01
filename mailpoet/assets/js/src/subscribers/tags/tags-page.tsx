import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { DataViews, View, Action } from '@wordpress/dataviews';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  wasInitialUrlStateReset,
  DataViewsFooter,
} from 'common/dataviews';
import { BackButton, PageHeader } from 'common/page-header';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { buildListFields } from './fields';
import { TagsForm } from './tags-form';
import {
  bulkDeleteTags,
  deleteTag,
  getSubscribersListingUrl,
  getTags,
} from './api';
import {
  requestFilterToViewFilters,
  viewFiltersToRequestFilter,
} from './filters';
import { buildTagsUrl, parseTagsUrlState } from './url-state';
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

// Column set is fixed; the subscriber-count filter is appended dynamically once
// the listing returns the site's buckets.
const baseFields = buildListFields([]);

function buildInitialView(): View {
  const preferredView = getDataViewsPreference(
    'tags',
    DEFAULT_VIEW,
    baseFields,
  );
  const state = parseTagsUrlState(window.location.href);

  return {
    ...preferredView,
    page: state.page,
    perPage: state.perPage ?? preferredView.perPage,
    search: state.search,
    filters: requestFilterToViewFilters(state.filter),
  };
}

function resetPageWhenQueryChanges(currentView: View, nextView: View): View {
  const searchChanged = (nextView.search ?? '') !== (currentView.search ?? '');
  const perPageChanged = nextView.perPage !== currentView.perPage;
  const filtersChanged =
    JSON.stringify(nextView.filters ?? []) !==
    JSON.stringify(currentView.filters ?? []);

  return {
    ...nextView,
    page: searchChanged || perPageChanged || filtersChanged ? 1 : nextView.page,
  };
}

export function TagsPage() {
  const [view, setView] = useState<View>(buildInitialView);
  const didMountRef = useRef(false);
  const [items, setItems] = useState<Tag[]>([]);
  const [meta, setMeta] = useState<TagListMeta>({
    count: 0,
    pages: 0,
    subscriber_count_buckets: [],
  });
  const [isLoading, setIsLoading] = useState(false);
  const [selection, setSelection] = useState<string[]>([]);
  const [formState, setFormState] = useState<FormState>({ kind: 'closed' });
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [globalSuccess, setGlobalSuccess] = useState<string | null>(null);
  const latestRequestIdRef = useRef(0);
  const completedInitialRequestRef = useRef(false);
  const updateView = useCallback((nextView: View): void => {
    setView((currentView) => {
      // DataViews can emit an early onChangeView that drops the URL-hydrated
      // page/search before the first fetch settles; ignore that reset so a
      // deep-linked listing keeps its state.
      if (
        !completedInitialRequestRef.current &&
        wasInitialUrlStateReset(currentView, nextView)
      ) {
        return currentView;
      }
      return resetPageWhenQueryChanges(currentView, nextView);
    });
  }, []);
  const handleViewChange = usePersistedDataViewsPreference(
    'tags',
    view,
    updateView,
  );

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
        filter: viewFiltersToRequestFilter(view.filters),
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
        completedInitialRequestRef.current = true;
        setIsLoading(false);
      }
    }
  }, [view.search, view.sort, view.page, view.perPage, view.filters]);

  useEffect(() => {
    void loadTags();
  }, [loadTags]);

  useEffect(() => {
    if (!didMountRef.current) {
      didMountRef.current = true;
      return;
    }
    const nextUrl = buildTagsUrl(
      window.location.href,
      view,
      viewFiltersToRequestFilter(view.filters),
    );
    window.history.replaceState({}, '', nextUrl);
  }, [view]);

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

  const fields = useMemo(
    () => buildListFields(meta.subscriber_count_buckets),
    [meta.subscriber_count_buckets],
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

      <div className="mailpoet-dataviews mailpoet-tags-dataviews">
        <DataViews<Tag>
          data={items}
          fields={fields}
          view={view}
          onChangeView={handleViewChange}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => String(item.id)}
          selection={selection}
          onChangeSelection={setSelection}
          isLoading={isLoading}
          empty={<div>{__('No tags yet.', 'mailpoet')}</div>}
        >
          <div className="mailpoet-dataviews__toolbar">
            <DataViews.Search label={__('Search tags', 'mailpoet')} />
            <DataViews.FiltersToggle />
            <div className="mailpoet-dataviews__toolbar-end">
              <DataViews.ViewConfig />
            </div>
          </div>
          <div className="mailpoet-dataviews__filters">
            <DataViews.Filters />
          </div>
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
