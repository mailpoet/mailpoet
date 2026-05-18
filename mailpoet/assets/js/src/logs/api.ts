import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
  ListingEnvelope,
  ListingQueryParams,
  ListingResponse,
} from 'common/dataviews';
import type { DateFilters } from './url-state';

declare global {
  interface Window {
    mailpoet_logs_api: {
      root: string;
      nonce: string;
    };
  }
}

let initialized = false;
function ensureInitialized(): void {
  if (initialized) return;
  const config = window.mailpoet_logs_api;
  apiFetch.use(apiFetch.createRootURLMiddleware(`${config.root}/`));
  apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
  initialized = true;
}

export type LogListingItem = {
  id: number;
  name: string;
  message: string;
  created_at: string | null;
};

export function buildLogsRequestParams(
  params: ListingQueryParams,
  dateFilters: DateFilters,
  legacyOffset?: number,
): ListingQueryParams {
  const search = params.search?.trim();
  const requestParams: ListingQueryParams = {
    ...params,
    search: search || undefined,
    filter: dateFilters,
  };

  if (legacyOffset !== undefined) {
    delete requestParams.page;
    requestParams.offset = legacyOffset;
    requestParams.limit = requestParams.per_page;
  }

  return requestParams;
}

export async function getLogs(
  params: ListingQueryParams,
): Promise<ListingResponse<LogListingItem>> {
  ensureInitialized();
  const response = await apiFetch<ListingEnvelope<LogListingItem>>({
    path: addQueryArgs('/mailpoet/v1/logs', params as Record<string, unknown>),
    method: 'GET',
  });
  return response.data;
}
