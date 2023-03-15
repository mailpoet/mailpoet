import apiFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import { dispatch, StoreDescriptor } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store } from './store';
import { ApiError } from '../api';

export const registerApiErrorHandler = (): void =>
  apiFetch.use(
    async (
      options: APIFetchOptions,
      next: (nextOptions: APIFetchOptions) => Promise<unknown>,
    ) => {
      try {
        const result = await next(options);
        return result;
      } catch (error) {
        const errorObject = error as ApiError;
        const status = errorObject.data?.status;
        const code = errorObject.code;

        if (code === 'mailpoet_automation_not_valid') {
          dispatch(store).setErrors({ steps: errorObject.data.errors });
          return undefined;
        }

        if (status && status >= 400 && status < 500) {
          const message = errorObject.message;
          void dispatch(noticesStore as StoreDescriptor).createErrorNotice(
            message ?? __('An unknown error occurred.', 'mailpoet'),
            { explicitDismiss: true },
          );
          dispatch(store).setErrors({ steps: [] });
          return undefined;
        }

        void dispatch(noticesStore as StoreDescriptor).createErrorNotice(
          __('An unknown error occurred.', 'mailpoet'),
          { explicitDismiss: true },
        );
        throw error;
      }
    },
  );
