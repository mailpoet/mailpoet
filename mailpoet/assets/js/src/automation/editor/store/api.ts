import apiFetch from '@wordpress/api-fetch';
import { api } from '../../config';

const apiUrl = `${api.root}/mailpoet/v1/automation/`;

export const initializeApi = () => {
  apiFetch.use(apiFetch.createRootURLMiddleware(apiUrl));
  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
};
