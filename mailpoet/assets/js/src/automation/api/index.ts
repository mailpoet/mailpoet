import apiFetch from '@wordpress/api-fetch';
import { api } from '../config';

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

export const initializeApi = () => {
  apiFetch.use((options, next) => {
    if (options.path && options.path.startsWith('/wc-analytics/')) {
      return apiFetch.createRootURLMiddleware(`${api.root}/`)(options, next);
    }
    return apiFetch.createRootURLMiddleware(apiUrl)(options, next);
  });

  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
};
