import apiFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { ApiError } from '../api';

function isManualStartApiPath(path: unknown): boolean {
  if (typeof path !== 'string') {
    return false;
  }

  const [pathname] = path.split('?', 1);
  const segments = pathname.split('/').filter(Boolean);
  const automationIndex = segments.indexOf('automations');
  const automationId = segments[automationIndex + 1];

  if (
    automationIndex === -1 ||
    segments[automationIndex + 2] !== 'manual-start'
  ) {
    return false;
  }

  const numericAutomationId = Number(automationId);
  return (
    Number.isInteger(numericAutomationId) &&
    numericAutomationId > 0 &&
    (segments.length === automationIndex + 3 ||
      (segments.length === automationIndex + 4 &&
        segments[automationIndex + 3] === 'preview'))
  );
}

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
          if (
            isManualStartApiPath(options.path) ||
            isManualStartApiPath((options as { url?: unknown }).url)
          ) {
            throw error;
          }

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
