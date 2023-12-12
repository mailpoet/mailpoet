import apiFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
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
        // do not report aborted requests as errors
        if (options.signal?.aborted) {
          return undefined;
        }

        const errorObject = error as ApiError;
        const status = errorObject.data?.status;

        if (status && status >= 400 && status < 500) {
          const message = errorObject.message;
          void dispatch(noticesStore).createErrorNotice(
            message ?? __('An unknown error occurred.', 'mailpoet'),
            { explicitDismiss: true },
          );
          return undefined;
        }

        void dispatch(noticesStore).createErrorNotice(
          __('An unknown error occurred.', 'mailpoet'),
          { explicitDismiss: true },
        );
        throw error;
      }
    },
  );
