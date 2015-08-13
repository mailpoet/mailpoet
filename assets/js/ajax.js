define('ajax', ['mailpoet', 'jquery'], function(MailPoet, jQuery) {
  "use strict";
  /**
  * MailPoet Ajax
  **/

  MailPoet.Ajax = {
      version: 0.1,
      options: {},
      defaults: {
        url: null,
        controller: 'dummy',
        action: 'test',
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

        if(this.options.url === null) {
          this.options.url = ajaxurl+'?action=mailpoet_ajax';
        }

        // routing
        this.options.url += '&mailpoet_controller='+this.options.controller;
        this.options.url += '&mailpoet_action='+this.options.action;
      },
      request: function(method, options) {
        // set options
        this.init(options);

        // make ajax request depending on method
        if(method === 'get') {
          jQuery.get(
            this.options.url,
            this.options.data,
            this.options.onSuccess,
            'json'
          );
        } else {
          jQuery.ajax(
            this.options.url,
            {
              data: JSON.stringify(this.options.data),
              processData: false,
              contentType: "application/json; charset=utf-8",
              type : method,
              dataType: 'json',
              success : this.options.onSuccess,
              error : this.options.onError
            }
          );
        }
      }
  };
});
