import _ from 'underscore';
import jQuery from 'jquery';
import { MailPoetI18n } from './i18n';

export type Response = {
  data: any; // eslint-disable-line @typescript-eslint/no-explicit-any
  meta?: any; // eslint-disable-line @typescript-eslint/no-explicit-any
};

export type ErrorResponse = {
  errors: {
    error?: string;
    message: string;
  }[];
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const isErrorResponse = (error: any): error is ErrorResponse =>
  error &&
  typeof error === 'object' &&
  'errors' in error &&
  Array.isArray(error.errors);

type ResponseType<R = Response, ER = ErrorResponse> = JQuery.Deferred<R, ER>;

function buildErrorResponse(message): ErrorResponse {
  return {
    errors: [
      {
        message,
      },
    ],
  };
}

function requestFailed(errorMessage, xhr) {
  if (xhr.responseJSON) {
    return xhr.responseJSON;
  }
  return buildErrorResponse(errorMessage.replace('%d', xhr.status));
}

// Renew MailPoet nonce via heartbeats to keep auth
// for AJAX requests on long-open pages
jQuery(document).on('heartbeat-tick.mailpoet-ajax', (_event, data) => {
  if (data.mailpoet_token) {
    window.mailpoet_token = data.mailpoet_token;
  }
});

export const MailPoetAjax = {
  version: 0.5,
  options: {},
  defaults: {
    url: null,
    api_version: null,
    endpoint: null,
    action: null,
    token: null,
    data: {},
  },
  post: function post<R = Response, ER = ErrorResponse>(
    options,
  ): ResponseType<R, ER> {
    return this.request('post', options);
  },
  get: function get(options): ResponseType {
    return this.request('get', options);
  },
  init: function init(options) {
    // merge options
    this.options = jQuery.extend({}, this.defaults, options);

    // set default url
    if (this.options.url === null) {
      this.options.url = window.ajaxurl;
    }

    // set default token
    if (this.options.token === null) {
      this.options.token = window.mailpoet_token;
    }

    // set default timeout
    if (this.options.timeout === null) {
      this.options.timeout = 0;
    }
  },
  getParams: function getParams() {
    return {
      action: 'mailpoet',
      api_version: this.options.api_version,
      token: this.options.token,
      endpoint: this.options.endpoint,
      method: this.options.action,
      data: this.options.data || {},
    };
  },
  constructGetUrl: function constructGetUrl(options): string {
    this.init(options);
    return `${this.options.url as string}?${jQuery.param(
      this.getParams() as object,
    )}`;
  },
  request: function request(method, options): ResponseType {
    // set options
    this.init(options);

    // set request params
    const params = this.getParams();

    // remove null values from the data object
    if (_.isObject(params.data)) {
      params.data = _.pick(params.data, (value) => value !== null);
    }

    // ajax request
    const deferred = jQuery.Deferred<Response, ErrorResponse>();
    const timeout = Math.ceil(this.options.timeout / 1000); // convert milliseconds to seconds
    jQuery[method]({
      url: this.options.url,
      data: params,
      success: null,
      dataType: 'json',
      timeout: this.options.timeout,
    }).then(
      (data: Response) => deferred.resolve(data),
      (failedXhr, textStatus) => {
        let errorData: ErrorResponse;
        if (textStatus === 'timeout') {
          errorData = buildErrorResponse(
            MailPoetI18n.t('ajaxTimeoutErrorMessage').replace(
              '%d',
              timeout.toString(),
            ),
          );
        } else {
          errorData = requestFailed(
            MailPoetI18n.t('ajaxFailedErrorMessage'),
            failedXhr,
          );
        }
        void deferred.reject(errorData);
      },
    );

    // clear options
    this.options = {};

    return deferred;
  },
} as const;
