import apiFetch from '@wordpress/api-fetch';
import { api } from '../config';

export * from './hooks';

const apiUrl = `${api.root}/mailpoet/v1/automation/`;

export type ApiError = {
  code?: string;
  message?: string;
  data?: {
    status?: number;
    details?: Error;
    params?: Record<string, string>;
  };
};

export const initializeApi = () => {
  apiFetch.use(apiFetch.createRootURLMiddleware(apiUrl));
  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
};
