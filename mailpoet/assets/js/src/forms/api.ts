import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
  ListingEnvelope,
  ListingQueryParams,
  ListingResponse,
} from 'common/dataviews';

declare global {
  interface Window {
    mailpoet_forms_api: {
      root: string;
      nonce: string;
    };
  }
}

let initialized = false;
function ensureInitialized(): void {
  if (initialized) return;
  const config = window.mailpoet_forms_api;
  apiFetch.use(apiFetch.createRootURLMiddleware(`${config.root}/`));
  apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  initialized = true;
}

export type FormListingItem = {
  id: string;
  name: string;
  status: 'enabled' | 'disabled';
  body: unknown;
  settings: {
    segments?: number[];
    segments_selected_by?: 'admin' | 'user';
    form_placement?: {
      fixed_bar?: { enabled?: string };
      below_posts?: { enabled?: string };
      popup?: { enabled?: string };
      slide_in?: { enabled?: string };
    };
  } | null;
  styles: string;
  signups: number;
  segments: Array<number | string>;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type BulkAction = 'trash' | 'restore' | 'delete';

export async function getForms(
  params: ListingQueryParams,
): Promise<ListingResponse<FormListingItem>> {
  ensureInitialized();
  const response = await apiFetch<ListingEnvelope<FormListingItem>>({
    path: addQueryArgs('/mailpoet/v1/forms', params as Record<string, unknown>),
    method: 'GET',
  });
  return response.data;
}

export async function bulkAction(
  action: BulkAction,
  ids: number[],
): Promise<number> {
  ensureInitialized();
  const response = await apiFetch<{ data: { action: BulkAction; count: number } }>({
    path: '/mailpoet/v1/forms/bulk-action',
    method: 'POST',
    data: { action, ids },
  });
  return response.data.count;
}
