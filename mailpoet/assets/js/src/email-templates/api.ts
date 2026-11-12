import apiFetch from '@wordpress/api-fetch';
import {
  configureRestApi,
  createRestListingLoader,
  restPost,
  restPut,
  type ListingQueryParams,
  type ListingResponse,
  type RestApiConfig,
} from 'common/dataviews';
import type { EmailPreset } from 'common/email-presets';

declare global {
  interface Window {
    mailpoet_email_templates_api: RestApiConfig;
  }
}

configureRestApi(window.mailpoet_email_templates_api);

const PATH = '/mailpoet/v1/email-presets';

export type PresetsGroup = 'all' | 'trash';

const loader = createRestListingLoader<EmailPreset>(PATH);

export function getPresets(
  params: ListingQueryParams,
  signal?: AbortSignal,
): Promise<ListingResponse<EmailPreset>> {
  return loader(params, signal);
}

type ItemEnvelope = { data: EmailPreset };

export async function createPreset(title: string): Promise<EmailPreset> {
  const response = await restPost<ItemEnvelope>(PATH, { title });
  return response.data;
}

export async function duplicatePreset(id: number): Promise<EmailPreset> {
  const response = await restPost<ItemEnvelope>(`${PATH}/${id}/duplicate`, {});
  return response.data;
}

export async function renamePreset(
  id: number,
  title: string,
): Promise<EmailPreset> {
  const response = await restPut<ItemEnvelope>(`${PATH}/${id}`, { title });
  return response.data;
}

export async function trashPreset(id: number): Promise<void> {
  await restPost(`${PATH}/${id}/trash`, {});
}

export async function restorePreset(id: number): Promise<void> {
  await restPost(`${PATH}/${id}/restore`, {});
}

export async function deletePreset(id: number): Promise<void> {
  await apiFetch({ path: `${PATH}/${id}`, method: 'DELETE' });
}

export async function getTrashedPresetIds(): Promise<number[]> {
  const ids: number[] = [];
  let page = 1;
  let pages: number;
  do {
    // eslint-disable-next-line no-await-in-loop -- pages are fetched one after another
    const result = await loader({ group: 'trash', page, per_page: 100 });
    ids.push(...result.items.map((item) => item.id));
    pages = result.meta.pages;
    page += 1;
  } while (page <= pages);
  return ids;
}

export async function getPresetPreview(
  id: number,
  signal?: AbortSignal,
): Promise<string> {
  const response = await apiFetch<{ data: { html: string } }>({
    path: `${PATH}/${id}/preview`,
    method: 'GET',
    signal,
  });
  return response.data.html;
}
