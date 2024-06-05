import { useState, useEffect } from 'react';
import { MailPoet } from 'mailpoet';

const CACHE_TIME = 60 * 60 * 1000; // 1 hour

const cacheSuccessfulResult = (storageKey: string) => {
  window.localStorage.setItem(storageKey, Date.now().toString());
};

const isSuccessfulResultCached = (storageKey: string) => {
  const lastSuccessAt = window.localStorage.getItem(storageKey);
  return (
    lastSuccessAt && Date.now() - parseInt(lastSuccessAt, 10) <= CACHE_TIME
  );
};

export const useServiceCheck = (
  endpoint: string,
  action: string,
  preconditionsCallback: () => boolean,
) => {
  const [serviceError, setServiceError] = useState<string | null>(null);

  useEffect(() => {
    if (preconditionsCallback && !preconditionsCallback()) return;

    // Don't make a request if we have a recently cached successful result
    const storageKey =
      `mailpoet_${endpoint}_${action}_last_success_at`.toLowerCase();
    if (isSuccessfulResultCached(storageKey)) return;

    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint,
      action,
    })
      .done(() => {
        cacheSuccessfulResult(storageKey);
        // Do not display the notice if there are no errors
        setServiceError(null);
      })
      .fail((errorResponse) => {
        const errObject = errorResponse?.errors?.[0];
        if (errObject.error && errObject.message) {
          // Display error notice if server responded with error message
          setServiceError(errObject.message);
        } else {
          // Do not display the notice if there is no server response
          setServiceError(null);
        }
      });
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  return serviceError;
};
