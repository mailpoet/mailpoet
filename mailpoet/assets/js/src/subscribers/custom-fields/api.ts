import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type { CustomField, CustomFieldListMeta } from './types';

type WindowApi = {
  mailpoet_custom_fields_api: {
    root: string;
    nonce: string;
  };
  mailpoet_custom_fields_subscribers_listing_url: string;
};

declare const window: Window & WindowApi;

let initialized = false;
function ensureInitialized(): void {
  if (initialized) return;
  const config = window.mailpoet_custom_fields_api;
  apiFetch.use(apiFetch.createRootURLMiddleware(`${config.root}/`));
  apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  initialized = true;
}

type ListParams = {
  search?: string;
  orderby?: string;
  order?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
};

type ApiEnvelope<T> = {
  data: T;
};

type ListResponse = ApiEnvelope<{
  items: CustomField[];
  meta: CustomFieldListMeta;
}>;

export async function getCustomFields(params: ListParams = {}): Promise<{
  items: CustomField[];
  meta: CustomFieldListMeta;
}> {
  ensureInitialized();
  const response = await apiFetch<ListResponse>({
    path: addQueryArgs('/mailpoet/v1/custom-fields', params),
    method: 'GET',
  });
  return response.data;
}

export function getSubscribersListingUrl(): string {
  return (
    window.mailpoet_custom_fields_subscribers_listing_url ??
    '?page=mailpoet-subscribers'
  );
}
