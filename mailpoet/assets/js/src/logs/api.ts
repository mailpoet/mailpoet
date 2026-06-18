import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
  ListingEnvelope,
  ListingQueryParams,
  ListingResponse,
} from 'common/dataviews';
import type { LogsFilter } from './url-state';

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
  level: number | null;
  message: string;
  created_at: string | null;
};

export function buildLogsRequestParams(
  params: ListingQueryParams,
  filters: LogsFilter,
): ListingQueryParams {
  const search = params.search?.trim();
  return {
    ...params,
    search: search || undefined,
    filter: filters,
  };
}

export async function getLogs(
  params: ListingQueryParams,
  signal?: AbortSignal,
): Promise<ListingResponse<LogListingItem>> {
  ensureInitialized();
  const response = await apiFetch<ListingEnvelope<LogListingItem>>({
    path: addQueryArgs('/mailpoet/v1/logs', params as Record<string, unknown>),
    method: 'GET',
    signal,
  });
  return response.data;
}
