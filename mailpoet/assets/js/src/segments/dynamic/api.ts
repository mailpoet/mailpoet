import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
  ListingEnvelope,
  ListingQueryParams,
  ListingResponse,
} from 'common/dataviews';

declare global {
  interface Window {
    mailpoet_segments_api: {
      root: string;
      nonce: string;
    };
  }
}

let initialized = false;
function ensureInitialized(): void {
  if (initialized) return;
  const config = window.mailpoet_segments_api;
  apiFetch.use(apiFetch.createRootURLMiddleware(`${config.root}/`));
  apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  initialized = true;
}

export type DynamicSegmentListingItem = {
  id: number;
  name: string;
  description?: string;
  count_all: string;
  count_subscribed: string;
  created_at: string;
  updated_at?: string;
  deleted_at: string | null;
  subscribers_url: string;
  is_plugin_missing: boolean;
  missing_plugin_message?: {
    message: string;
  };
};

export type DynamicSegmentBulkAction = 'trash' | 'restore' | 'delete';

export type DynamicSegmentBulkActionResult = {
  updated: number;
  deleted: number;
  skipped: number;
  errors: Array<{ id: number | null; message: string }>;
};

export async function getDynamicSegments(
  params: ListingQueryParams,
): Promise<ListingResponse<DynamicSegmentListingItem>> {
  ensureInitialized();
  const response = await apiFetch<ListingEnvelope<DynamicSegmentListingItem>>({
    path: addQueryArgs(
      '/mailpoet/v1/dynamic-segments',
      params as Record<string, unknown>,
    ),
    method: 'GET',
  });
  return response.data;
}

export async function bulkAction(
  action: DynamicSegmentBulkAction,
  ids: number[],
  params?: Partial<ListingQueryParams> & { select_all?: boolean },
): Promise<DynamicSegmentBulkActionResult> {
  ensureInitialized();
  const response = await apiFetch<{ data: DynamicSegmentBulkActionResult }>({
    path: '/mailpoet/v1/dynamic-segments/bulk-action',
    method: 'POST',
    data: {
      action,
      ids,
      ...params,
    },
  });
  return response.data;
}
