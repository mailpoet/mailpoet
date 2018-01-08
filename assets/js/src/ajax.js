function requestFailed(errorMessage, xhr) {
  if (xhr.responseJSON) {
    return xhr.responseJSON;
  }
  return {
    errors: [
      {
        message: errorMessage.replace('%d', xhr.status)
      }
    ]
  };
}

define('ajax', ['mailpoet', 'jquery', 'underscore'], function ajax(mp, jQuery, _) {
  var MailPoet = mp;

  MailPoet.Ajax = {
    version: 0.5,
    options: {},
    defaults: {
      url: null,
      api_version: null,
      endpoint: null,
      action: null,
      token: null,
      data: {}
    },
    post: function post(options) {
      return this.request('post', options);
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
    },
    getParams: function getParams() {
      return {
        action: 'mailpoet',
        api_version: this.options.api_version,
        token: this.options.token,
        endpoint: this.options.endpoint,
        method: this.options.action,
        data: this.options.data || {}
      };
    },
    request: function request(method, options) {
      var params;
      var deferred;
      // set options
      this.init(options);

      // set request params
      params = this.getParams();

      // remove null values from the data object
      if (_.isObject(params.data)) {
        params.data = _.pick(params.data, function IsNotNull(value) {
          return (value !== null);
        });
      }

      // ajax request
      deferred = jQuery.post(
          this.options.url,
          params,
          null,
          'json'
        ).then(function resultHandler(data) {
          return data;
        }, _.partial(requestFailed, MailPoet.I18n.t('ajaxFailedErrorMessage')));

        // clear options
      this.options = {};

      return deferred;
    }
  };
});
