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
  raw_message: string | null;
  context: Record<string, unknown>;
  created_at: string | null;
};

type ApiEnvelope<T> = {
  data: T;
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

let logNamesRequest: Promise<string[]> | null = null;

// Loads the logger names for the Name filter. A successful request is shared
// for the whole page load, so remounting the listing does not refetch. A
// failure resolves to no options (the listing still works) and is not cached,
// so the next filter open retries.
export function getLogNames(): Promise<string[]> {
  if (logNamesRequest === null) {
    ensureInitialized();
    const request = apiFetch<ApiEnvelope<{ names: string[] }>>({
      path: '/mailpoet/v1/logs/names',
      method: 'GET',
    })
      .then((response) => response.data.names)
      .catch(() => {
        if (logNamesRequest === request) {
          logNamesRequest = null;
        }
        return [];
      });
    logNamesRequest = request;
  }
  return logNamesRequest;
}

// Deletes the logs matching the listing's current filters and search, so a
// deletion removes exactly the rows the filtered listing shows. `all` confirms
// the unrestricted case (no filters, no search) where every log is removed.
export async function deleteLogs(
  filter: LogsFilter,
  search: string | undefined,
  all: boolean,
): Promise<number> {
  ensureInitialized();
  const response = await apiFetch<ApiEnvelope<{ deleted: number }>>({
    path: '/mailpoet/v1/logs/delete',
    method: 'POST',
    data: {
      filter,
      search: search?.trim() || undefined,
      all,
    },
  });
  return response.data.deleted;
}
