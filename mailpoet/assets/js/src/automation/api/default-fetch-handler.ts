/* eslint-disable */
/**
 * This is a temporary solution to handle firewall blocking our requests.
 * It will be removed once we have a proper solution to handle this.
 *
 * Disabled eslint to match the original file.
 *
 * Copied from https://github.com/WordPress/gutenberg/blob/5b9c8dddca987e19b2f158f377b98d153071e0f3/packages/api-fetch/src/index.js
 * @todo Remove this file once the issue is fixed.
 *
 * @package MailPoet/Automation
 */

import { __ } from '@wordpress/i18n';

const DEFAULT_HEADERS = {
  Accept: 'application/json, */*;q=0.1',
};

const DEFAULT_OPTIONS = {
  credentials: 'include',
};

const parseJsonAndNormalizeError = (response) => {
  const invalidJsonError = {
    code: 'invalid_json',
    message: __('The response is not a valid JSON response.'),
  };

  if (!response || !response.json) {
    throw invalidJsonError;
  }

  return response.json().catch(() => {
    throw invalidJsonError;
  });
};

const parseAndThrowError = (response, shouldParseResponse = true) => {
  if (!shouldParseResponse) {
    throw response;
  }

  return parseJsonAndNormalizeError(response).then((error) => {
    const unknownError = {
      code: 'unknown_error',
      message: __('An unknown error occurred.'),
    };

    throw error || unknownError;
  });
};

const parseResponse = (response, shouldParseResponse = true) => {
  if (shouldParseResponse) {
    if (response.status === 204) {
      return null;
    }

    return response.json ? response.json() : Promise.reject(response);
  }

  return response;
};

const parseResponseAndNormalizeError = (
  response,
  shouldParseResponse = true,
) => {
  return Promise.resolve(parseResponse(response, shouldParseResponse)).catch(
    (res) => parseAndThrowError(res, shouldParseResponse),
  );
};

const checkStatus = (response) => {
  if (response.status >= 200 && response.status < 300) {
    return response;
  }

  throw response;
};

export const defaultFetchHandler = (nextOptions) => {
  const { url, path, data, parse = true, ...remainingOptions } = nextOptions;
  let { body, headers } = nextOptions;

  // Merge explicitly-provided headers with default values.
  headers = { ...DEFAULT_HEADERS, ...headers };

  // The `data` property is a shorthand for sending a JSON body.
  if (data) {
    body = JSON.stringify(data);
    headers['Content-Type'] = 'application/json';
  }

  const responsePromise = window.fetch(
    // Fall back to explicitly passing `window.location` which is the behavior if `undefined` is passed.
    url || path || window.location.href,
    {
      ...DEFAULT_OPTIONS,
      ...remainingOptions,
      body,
      headers,
    },
  );

  return responsePromise.then(
    (value) =>
      Promise.resolve(value)
        .then(checkStatus)
        .catch((response) => parseAndThrowError(response, parse))
        .then((response) => parseResponseAndNormalizeError(response, parse)),
    (err) => {
      // Re-throw AbortError for the users to handle it themselves.
      if (err && err.name === 'AbortError') {
        throw err;
      }

      // Otherwise, there is most likely no network connection.
      // Unfortunately the message might depend on the browser.
      throw {
        code: 'fetch_error',
        message: __('You are probably offline.'),
      };
    },
  );
};
