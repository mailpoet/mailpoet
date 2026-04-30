import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { DataViews, View } from '@wordpress/dataviews';
import { BackButton, PageHeader } from 'common/page-header';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { listFields } from './fields';
import { getCustomFields, getSubscribersListingUrl } from './api';
import type {
  ApiErrorResponse,
  CustomField,
  CustomFieldListMeta,
} from './types';

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

export function CustomFieldsPage() {
  const [view, setView] = useState<View>(DEFAULT_VIEW);
  const [items, setItems] = useState<CustomField[]>([]);
  const [meta, setMeta] = useState<CustomFieldListMeta>({
    count: 0,
    pages: 0,
  });
  const [isLoading, setIsLoading] = useState(false);
  const [globalError, setGlobalError] = useState<string | null>(null);
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
        apiError?.message || __('Failed to load custom fields.', 'mailpoet'),
      );
    } finally {
      if (requestId === latestRequestIdRef.current) {
        setIsLoading(false);
      }
    }
  }, [view.search, view.sort, view.page, view.perPage]);

  useEffect(() => {
    void loadCustomFields();
  }, [loadCustomFields]);

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

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
      />

      {globalError && (
        <Notice status="error" onRemove={() => setGlobalError(null)}>
          {globalError}
        </Notice>
      )}

      <div className="mailpoet-custom-fields-dataviews">
        <DataViews<CustomField>
          data={items}
          fields={listFields}
          view={view}
          onChangeView={setView}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => String(item.id)}
          isLoading={isLoading}
          empty={<div>{__('No custom fields yet.', 'mailpoet')}</div>}
        >
          <div className="mailpoet-custom-fields-dataviews__toolbar">
            <DataViews.Search label={__('Search custom fields', 'mailpoet')} />
          </div>
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>
    </>
  );
}
