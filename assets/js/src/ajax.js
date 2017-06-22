function requestFailed(xhr) {
  if (xhr.responseJSON) {
    return xhr.responseJSON;
  }
  var message = "An error has happened while performing a request, the server has responded with response code "
    + xhr.status;
  return {
    errors: [
      {
        message: message
      }
    ]
  }
}

define('ajax', ['mailpoet', 'jquery', 'underscore'], function(MailPoet, jQuery, _) {

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
      post: function(options) {
        return this.request('post', options);
      },
      init: function(options) {
        // merge options
        this.options = jQuery.extend({}, this.defaults, options);

        // set default url
        if(this.options.url === null) {
          this.options.url = ajaxurl;
        }

        // set default token
        if(this.options.token === null) {
          this.options.token = window.mailpoet_token;
        }
      },
      getParams: function() {
        return {
          action: 'mailpoet',
          api_version: this.options.api_version,
          token: this.options.token,
          endpoint: this.options.endpoint,
          method: this.options.action,
          data: this.options.data || {}
        }
      },
      request: function(method, options) {
        // set options
        this.init(options);

        // set request params
        var params = this.getParams();

        // remove null values from the data object
        if (_.isObject(params.data)) {
          params.data = _.pick(params.data, function(value) {
            return (value !== null)
          })
        }

        // ajax request
        var deferred = jQuery.post(
          this.options.url,
          params,
          null,
          'json'
        ).then(function(data) {
          return data;
        }, requestFailed);

        // clear options
        this.options = {};

        return deferred;
      }
  };
});
