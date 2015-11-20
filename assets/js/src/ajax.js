define('ajax', ['mailpoet', 'jquery'], function(MailPoet, jQuery) {
  'use strict';
  MailPoet.Ajax = {
      version: 0.5,
      options: {},
      defaults: {
        url: null,
        endpoint: null,
        action: null,
        token: null,
        data: {},
        onSuccess: function(data, textStatus, xhr) {},
        onError: function(xhr, textStatus, errorThrown) {},
        onComplete: function(xhr) {}
      },
      get: function(options) {
        return this.request('get', options);
      },
      post: function(options) {
        return this.request('post', options);
      },
      head: function(options) {
        return this.request('head', options);
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

        // make ajax request depending on method
        if(method === 'get') {
          jqXHR = jQuery.get(
            this.options.url,
            params,
            this.options.onSuccess,
            'json'
          );
        }
        else if (method === 'head') {
          jqXHR = jQuery.ajax({
            url: this.options.url,
            type : 'head',
            complete : this.options.onComplete
          });
        }
        else {
          jqXHR = jQuery.ajax({
            url: this.options.url,
            type : 'post',
            data: params,
            dataType: 'json',
            success : this.options.onSuccess,
            error : this.options.onError,
            complete : this.options.onComplete
          });
        }

        // clear options
        this.options = {};

        return jqXHR;
      }
  };
});
