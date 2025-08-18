import apiFetch from '@wordpress/api-fetch';
import { api } from '../config';
import { defaultFetchHandler } from './default-fetch-handler';
import { MailPoet } from '../../mailpoet';

const apiUrl = `${api.root}/mailpoet/v1/`;

export type ApiError = {
  code?: string;
  message?: string;
  data?: {
    status?: number;
    details?: Error;
    params?: Record<string, string>;
    errors?: unknown[];
  };
};

const OVERRIDE_METHODS = new Set(['PATCH', 'PUT', 'DELETE']);

// This is mainly used to remove the httpV1Middleware.
export const useCustomFetchHandler = (nextOptions) => {
  if (!MailPoet.FeaturesController.isSupported('remove_http_v1_middleware')) {
    return defaultFetchHandler(nextOptions);
  }

  let { method } = nextOptions;
  const headers: Record<string, string> = nextOptions.headers || {};

  const headerName = 'X-HTTP-Method-Override';

  // we need to override the header here because custom middleware are added after core middlewares
  // and this is the only place to update the core middleware.
  if (
    Object.prototype.hasOwnProperty.call(headers, headerName) &&
    OVERRIDE_METHODS.has((headers[headerName] || '').toUpperCase())
  ) {
    method = headers[headerName];
    delete headers[headerName];
  }

  return defaultFetchHandler({
    ...nextOptions,
    method,
    headers,
  });
};

export const initializeApi = () => {
  apiFetch.use((options, next) => {
    if (
      options.path &&
      (options.path.startsWith('/wc-analytics/') ||
        options.path.startsWith('/wp/v2/'))
    ) {
      return apiFetch.createRootURLMiddleware(`${api.root}/`)(options, next);
    }
    return apiFetch.createRootURLMiddleware(apiUrl)(options, next);
  });

  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
  apiFetch.setFetchHandler(useCustomFetchHandler);
};
