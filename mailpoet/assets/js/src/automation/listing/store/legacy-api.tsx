import apiFetch from '@wordpress/api-fetch';
import { AutomationStatus } from '../automation';

type ApiOptions = {
  endpoint: string;
  method: string;
  [key: string]: string;
};

export type ListingItem = {
  id: number;
  type: 'welcome' | 'automatic';
  subject: string;
  status: AutomationStatus;
  deleted_at: string | null;
};

export const legacyApiFetch = ({ endpoint, method, ...params }: ApiOptions) =>
  apiFetch({
    url: window.ajaxurl,
    method: 'POST',
    headers: {
      'content-type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'mailpoet',
      token: window.mailpoet_token,
      api_version: 'v1',
      endpoint,
      method,
      ...params,
    }),
  });
