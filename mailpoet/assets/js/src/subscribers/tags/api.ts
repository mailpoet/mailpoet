import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type { Tag, TagListMeta } from './types';

type WindowApi = {
  mailpoet_tags_api: {
    root: string;
    nonce: string;
  };
  mailpoet_tags_subscribers_listing_url: string;
};

declare const window: Window & WindowApi;

let initialized = false;
function ensureInitialized(): void {
  if (initialized) return;
  const config = window.mailpoet_tags_api;
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
  items: Tag[];
  meta: TagListMeta;
}>;

type ItemResponse = ApiEnvelope<Tag>;

export async function getTags(params: ListParams = {}): Promise<{
  items: Tag[];
  meta: TagListMeta;
}> {
  ensureInitialized();
  const response = await apiFetch<ListResponse>({
    path: addQueryArgs('/mailpoet/v1/tags', params),
    method: 'GET',
  });
  return response.data;
}

export async function createTag(data: {
  name: string;
  description?: string;
}): Promise<Tag> {
  ensureInitialized();
  const response = await apiFetch<ItemResponse>({
    path: '/mailpoet/v1/tags',
    method: 'POST',
    data,
  });
  return response.data;
}

export async function updateTag(
  id: number,
  data: { name?: string; description?: string },
): Promise<Tag> {
  ensureInitialized();
  const response = await apiFetch<ItemResponse>({
    path: `/mailpoet/v1/tags/${id}`,
    method: 'PUT',
    data,
  });
  return response.data;
}

export async function deleteTag(id: number): Promise<void> {
  ensureInitialized();
  await apiFetch({
    path: `/mailpoet/v1/tags/${id}`,
    method: 'DELETE',
  });
}

export async function bulkDeleteTags(ids: number[]): Promise<number> {
  ensureInitialized();
  const response = await apiFetch<ApiEnvelope<{ deleted: number }>>({
    path: '/mailpoet/v1/tags/bulk-delete',
    method: 'POST',
    data: { ids },
  });
  return response.data.deleted;
}

export function getSubscribersListingUrl(tagId?: number): string {
  const baseUrl =
    window.mailpoet_tags_subscribers_listing_url ??
    '?page=mailpoet-subscribers';
  if (tagId === undefined) {
    return baseUrl;
  }
  return `${baseUrl}#/filter[tag=${tagId}]`;
}
