import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { api } from '../../config';
import { AutomationStatus } from '../automation';

type ApiOptions = {
  endpoint: string;
  method: string;
  [key: string]: string;
};

export type ListingItem = {
  id: number;
  type: 'welcome' | 'automatic';
  subject: string;
  status: AutomationStatus;
  deleted_at: string | null;
  total_scheduled: number;
  total_sent: number;
  options: {
    event: string;
    group: string;
    segment: string;
    role: string;
    afterTimeType: string;
    afterTimeNumber: number;
    meta?: string;
  };
};

type ListingResponse = {
  data: {
    items: ListingItem[];
    meta: {
      pages: number;
    };
  };
};

const LEGACY_AUTOMATIONS_PER_PAGE = 100;

export const legacyApiFetch = ({ endpoint, method, ...params }: ApiOptions) =>
  apiFetch({
    url: window.ajaxurl,
    method: 'POST',
    headers: {
      'content-type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'mailpoet',
      token: window.mailpoet_token,
      api_version: 'v1',
      endpoint,
      method,
      ...params,
    }),
  });

export async function getLegacyNewsletters(
  type: ListingItem['type'],
): Promise<ListingItem[]> {
  let page = 1;
  let pages = 1;
  const items: ListingItem[] = [];

  do {
    // eslint-disable-next-line no-await-in-loop
    const response = await apiFetch<ListingResponse>({
      url: addQueryArgs(`${api.root}/mailpoet/v1/newsletters`, {
        type,
        page,
        per_page: LEGACY_AUTOMATIONS_PER_PAGE,
      }),
      method: 'GET',
      headers: {
        'X-WP-Nonce': api.nonce,
      },
    });
    items.push(...response.data.items);
    pages = response.data.meta.pages;
    page += 1;
  } while (page <= pages);

  return items;
}
