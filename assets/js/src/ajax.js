import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import _ from 'underscore';

function requestFailed(errorMessage, xhr) {
  if (xhr.responseJSON) {
    return xhr.responseJSON;
  }
  return {
    errors: [
      {
        message: errorMessage.replace('%d', xhr.status),
      },
    ],
  };
}

// Renew MailPoet nonce via heartbeats to keep auth
// for AJAX requests on long-open pages
jQuery(document).on('heartbeat-tick.mailpoet-ajax', (event, data) => {
  if (data.mailpoet_token) {
    window.mailpoet_token = data.mailpoet_token;
  }
});

MailPoet.Ajax = {
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
  post: function post(options) {
    return this.request('post', options);
  },
  get: function get(options) {
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
    if (this.options.token === null) {
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
  constructGetUrl: function constructGetUrl(options) {
    this.init(options);
    return this.options.url + '?' + jQuery.param(this.getParams());
  },
  request: function request(method, options) {
    var params;
    // set options
    this.init(options);

    // set request params
    params = this.getParams();

    // remove null values from the data object
    if (_.isObject(params.data)) {
      params.data = _.pick(params.data, function isNotNull(value) {
        return (value !== null);
      });
    }

    // ajax request
    const deferred = jQuery.Deferred();
    jQuery[method]({
      url: this.options.url,
      data: params,
      success: null,
      dataType: 'json',
      timeout: this.options.timeout,
    }).then(deferred.resolve, (failedXhr) => {
      const errorData = requestFailed(MailPoet.I18n.t('ajaxFailedErrorMessage'), failedXhr);
      deferred.reject(errorData);
    });

    // clear options
    this.options = {};

    return deferred;
  },
};
