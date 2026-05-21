import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
  ListingEnvelope,
  ListingQueryParams,
  ListingResponse,
} from './types';

export type RestApiConfig = {
  root: string;
  nonce: string;
};

/**
 * Initialize `@wordpress/api-fetch` against MailPoet's REST root + nonce. Idempotent,
 * so multiple listings on the same page can call it without registering the
 * middleware twice.
 *
 * Pass the same config object exposed by the admin page (e.g.
 * `window.mailpoet_subscribers_api` = `{root, nonce}`).
 */
let configuredRoot = '';
let configuredNonce = '';
let isConfigured = false;

function ensureTrailingSlash(value: string): string {
  return value.endsWith('/') ? value : `${value}/`;
}

function ensureLeadingSlash(value: string): string {
  return value.startsWith('/') ? value : `/${value}`;
}

function configureMiddleware(): void {
  if (isConfigured) return;

  apiFetch.use((options, next) => {
    const path = typeof options.path === 'string' ? options.path : '';
    const hasUrl = typeof options.url === 'string' && options.url.length > 0;
    const normalizedPath = path ? ensureLeadingSlash(path) : path;
    const root = ensureTrailingSlash(configuredRoot);

    return next({
      ...options,
      ...(hasUrl ? {} : { path: `${root}${normalizedPath.slice(1)}` }),
    });
  });

  apiFetch.use((options, next) =>
    next({
      ...options,
      headers: {
        ...(options.headers || {}),
        'X-WP-Nonce': configuredNonce,
      },
    }),
  );

  isConfigured = true;
}

export function configureRestApi(config: RestApiConfig): void {
  if (!config) return;
  configuredRoot = config.root;
  configuredNonce = config.nonce;
  configureMiddleware();
}

/**
 * Build a `loadListing` function suitable for {@link useDataViewsQuery} that talks to
 * any endpoint exposed via {@link \MailPoet\API\REST\AbstractListingEndpoint}.
 *
 * Extra params can be added per-call (e.g. listing group, filters) — they are merged
 * into the query string after the standard pagination/sort/search params.
 *
 * Errors are normalized: the rejection always carries a string `message` so the
 * generic loading hook can surface it without re-parsing transport errors.
 */
type WpApiErrorShape = {
  code?: string;
  message?: string;
  data?: { status?: number; errors?: Record<string, string> };
};

function normalizeApiError(error: unknown): Error {
  if (error instanceof Error) return error;
  if (typeof error === 'object' && error !== null) {
    const shape = error as WpApiErrorShape;
    const message =
      shape.message ||
      (shape.data?.errors ? Object.values(shape.data.errors).join('\n') : '');
    const normalized = new Error(message || 'Request failed.');
    // Preserve the raw response so callers that need the error code / status
    // (e.g. inspecting `confirmation_disabled` to swap in a richer notice)
    // can still read it.
    Object.assign(normalized, {
      code: shape.code,
      status: shape.data?.status,
      errors: shape.data?.errors,
    });
    return normalized;
  }
  return new Error(String(error));
}

export type RestApiError = Error & {
  code?: string;
  status?: number;
  errors?: Record<string, string>;
};

export function createRestListingLoader<T>(
  path: string,
): (
  params: ListingQueryParams & Record<string, unknown>,
  signal?: AbortSignal,
) => Promise<ListingResponse<T>> {
  return async (params, signal) => {
    try {
      const response = await apiFetch<ListingEnvelope<T>>({
        path: addQueryArgs(path, params as Record<string, unknown>),
        method: 'GET',
        signal,
      });
      return response.data;
    } catch (error) {
      throw normalizeApiError(error);
    }
  };
}

/**
 * Thin POST helper for non-listing REST routes (e.g. bulk-action). Shares the
 * same nonce middleware + error normalization as the listing loader so callers
 * see one consistent error shape.
 */
export async function restPost<T>(
  path: string,
  data: Record<string, unknown>,
): Promise<T> {
  try {
    return await apiFetch<T>({
      path,
      method: 'POST',
      data,
    });
  } catch (error) {
    throw normalizeApiError(error);
  }
}

/**
 * Thin PUT helper for non-listing REST routes (e.g. updating a single resource).
 * Shares the same nonce middleware + error normalization as {@link restPost}.
 */
export async function restPut<T>(
  path: string,
  data: Record<string, unknown>,
): Promise<T> {
  try {
    return await apiFetch<T>({
      path,
      method: 'PUT',
      data,
    });
  } catch (error) {
    throw normalizeApiError(error);
  }
}
