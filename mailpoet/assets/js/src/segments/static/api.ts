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

export type SegmentListingItem = {
  id: string;
  name: string;
  description: string;
  type: string;
  average_engagement_score: number;
  show_in_manage_subscription_page: boolean | number;
  subscribers_count: {
    subscribed?: number;
    unconfirmed?: number;
    unsubscribed?: number;
    inactive?: number;
    bounced?: number;
  };
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  subscribers_url: string;
};

export type SegmentBulkAction = 'trash' | 'restore' | 'delete' | 'empty_trash';

export type SegmentBulkActionResult = {
  updated: number;
  deleted: number;
  skipped: number;
  errors: Array<{ id: number | null; message: string }>;
};

export async function getSegments(
  params: ListingQueryParams,
): Promise<ListingResponse<SegmentListingItem>> {
  ensureInitialized();
  const response = await apiFetch<ListingEnvelope<SegmentListingItem>>({
    path: addQueryArgs(
      '/mailpoet/v1/segments',
      params as Record<string, unknown>,
    ),
    method: 'GET',
  });
  return response.data;
}

export async function bulkAction(
  action: SegmentBulkAction,
  ids: number[],
  params?: Partial<ListingQueryParams> & { select_all?: boolean },
): Promise<SegmentBulkActionResult> {
  ensureInitialized();
  const response = await apiFetch<{ data: SegmentBulkActionResult }>({
    path: '/mailpoet/v1/segments/bulk-action',
    method: 'POST',
    data: {
      action,
      ids,
      ...params,
    },
  });
  return response.data;
}
