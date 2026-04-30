import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
  CustomField,
  CustomFieldPayload,
  CustomFieldListGroup,
  CustomFieldListMeta,
} from './types';

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
  group?: 'all' | 'trash';
};

type ApiEnvelope<T> = {
  data: T;
};

type ListResponse = ApiEnvelope<{
  items: CustomField[];
  meta: CustomFieldListMeta;
  groups: CustomFieldListGroup[];
}>;

type ItemResponse = ApiEnvelope<CustomField>;

export async function getCustomFields(params: ListParams = {}): Promise<{
  items: CustomField[];
  meta: CustomFieldListMeta;
  groups: CustomFieldListGroup[];
}> {
  ensureInitialized();
  const response = await apiFetch<ListResponse>({
    path: addQueryArgs('/mailpoet/v1/custom-fields', params),
    method: 'GET',
  });
  return response.data;
}

export async function createCustomField(
  data: CustomFieldPayload,
): Promise<CustomField> {
  ensureInitialized();
  const response = await apiFetch<ItemResponse>({
    path: '/mailpoet/v1/custom-fields',
    method: 'POST',
    data,
  });
  return response.data;
}

export async function updateCustomField(
  id: number,
  data: CustomFieldPayload,
): Promise<CustomField> {
  ensureInitialized();
  const response = await apiFetch<ItemResponse>({
    path: `/mailpoet/v1/custom-fields/${id}`,
    method: 'PUT',
    data,
  });
  return response.data;
}

export type CustomFieldBulkAction =
  | 'trash'
  | 'restore'
  | 'delete'
  | 'empty_trash';

export async function bulkAction(
  action: CustomFieldBulkAction,
  ids: number[] = [],
): Promise<number> {
  ensureInitialized();
  const response = await apiFetch<ApiEnvelope<{ count: number }>>({
    path: '/mailpoet/v1/custom-fields/bulk-action',
    method: 'POST',
    data: action === 'empty_trash' ? { action } : { action, ids },
  });
  return response.data.count;
}

export function getSubscribersListingUrl(): string {
  return (
    window.mailpoet_custom_fields_subscribers_listing_url ??
    '?page=mailpoet-subscribers'
  );
}
