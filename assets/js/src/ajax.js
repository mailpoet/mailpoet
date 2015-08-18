define('ajax', ['mailpoet', 'jquery'], function(MailPoet, jQuery) {
  "use strict";
  MailPoet.Ajax = {
      version: 0.1,
      options: {},
      defaults: {
        url: null,
        endpoint: null,
        action: null,
        data: {},
        onSuccess: function(data, textStatus, xhr) {},
        onError: function(xhr, textStatus, errorThrown) {}
      },
      get: function(options) {
        this.request('get', options);
      },
      post: function(options) {
        this.request('post', options);
      },
      delete: function(options) {
        this.request('delete', options);
      },
      init: function(options) {
        // merge options
        this.options = jQuery.extend({}, this.defaults, options);

        // set default url
        if(this.options.url === null) {
          this.options.url = ajaxurl;
        }
      },
      request: function(method, options) {
        // set options
        this.init(options);

        // set request params
        var params = {
          action: 'mailpoet',
          token: mailpoet_token,
          endpoint: this.options.endpoint,
          method: this.options.action,
          data: this.options.data
        };

        // make ajax request depending on method
        if(method === 'get') {
          jQuery.get(
            this.options.url,
            params,
            this.options.onSuccess,
            'json'
          );
        } else {
          jQuery.ajax({
            url: this.options.url,
            type : 'post',
            data: params,
            dataType: 'json',
            success : this.options.onSuccess,
            error : this.options.onError
          });
        }
      }
  };
});
