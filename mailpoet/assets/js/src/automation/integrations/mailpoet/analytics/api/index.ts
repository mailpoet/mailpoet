import apiFetch from '@wordpress/api-fetch';
import { api } from '../config';

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
  const apiUrl = `${api.root}/mailpoet/v1/`;
  apiFetch.use(apiFetch.createRootURLMiddleware(apiUrl));
  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
};
