define('ajax', ['mailpoet', 'jquery', 'underscore'], function(MailPoet, jQuery, _) {
  'use strict';
  MailPoet.Ajax = {
      version: 0.5,
      options: {},
      defaults: {
        url: null,
        endpoint: null,
        action: null,
        token: null,
        data: {}
      },
      get: function(options) {
        return this.request('get', options);
      },
      post: function(options) {
        return this.request('post', options);
      },
      delete: function(options) {
        return this.request('delete', options);
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
        var jqXHR;

        // remove null values from the data object
        if (_.isObject(params.data)) {
          params.data = _.pick(params.data, function(value) {
            return (value !== null)
          })
        }

        // make ajax request depending on method
        if(method === 'get') {
          jqXHR = jQuery.get(
            this.options.url,
            params,
            this.options.onSuccess,
            'json'
          );
        } else {
          jqXHR = jQuery.ajax({
            url: this.options.url,
            type : 'post',
            data: params,
            dataType: 'json'
          });
        }

        // clear options
        this.options = {};

        return jqXHR;
      }
  };
});
