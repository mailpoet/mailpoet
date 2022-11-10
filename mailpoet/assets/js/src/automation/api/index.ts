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
  apiFetch.use(apiFetch.createRootURLMiddleware(apiUrl));
  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
};
