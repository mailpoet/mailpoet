define('notice', ['mailpoet', 'jquery'], function(MailPoet, jQuery) {
  "use strict";
  /*==================================================================================================

      MailPoet Notice:

          description: Handles notices
          version: 0.2
          author: Jonathan Labreuille
          company: Wysija
          dependencies: jQuery

      Usage:

      // success message (static: false)
          MailPoet.Notice.success('Yatta!');

          // error message (static: false)
          MailPoet.Notice.error('Boo!');

          // system message (static: true)
          MailPoet.Notice.system('You need to updated ASAP!');

      Examples:

          MailPoet.Notice.success('-  success #1 -');
          setTimeout(function() {
              MailPoet.Notice.success('-  success #2 -');
              setTimeout(function() {
                  MailPoet.Notice.error('-  error -');
                  setTimeout(function() {
                      MailPoet.Notice.system('- system -');

                      setTimeout(function() {
                          MailPoet.Notice.hide();
                      }, 2500);
                  }, 300);
              }, 400);
          }, 500);

  ==================================================================================================*/

  MailPoet.Notice = {
      version: 0.2,
      // default options
      defaults: {
          type: 'success',
          message: '',
          static: false,
          hideClose: false,
          id: null,
          positionAfter: false,
          scroll: false,
          timeout: 2000,
          onOpen: null,
          onClose: null
      },
      options: {},
      init: function(options) {
          // set options
          this.options = jQuery.extend({}, this.defaults, options);

          // clone element
          this.element = jQuery('#mailpoet_notice_'+this.options.type).clone();

          // add data-id to the element
          if (this.options.id) this.element.attr('data-id', 'notice_' + this.options.id);

          // remove id from clone
          this.element.removeAttr('id');

          // insert notice after its parent
          var positionAfter;
          if (typeof this.options.positionAfter === 'object') {
            positionAfter = this.options.positionAfter;
          } else if (typeof this.options.positionAfter === 'string') {
            positionAfter = jQuery(this.options.positionAfter);
          } else {
            positionAfter = jQuery('#mailpoet_notice_'+this.options.type);
          }
          positionAfter.after(this.element);

          // setup onClose callback
          var onClose = null;
          if(this.options.onClose !== null) {
              onClose = this.options.onClose;
          }

          // listen to remove event
          jQuery(this.element).on('close', function() {
              jQuery(this).fadeOut(200, function() {
                  // on close callback
                  if(onClose !== null) {
                      onClose();
                  }
                  // remove notice
                  jQuery(this).remove();
              });
          }.bind(this.element));

          // listen to message event
          jQuery(this.element).on('message', function(e, message) {
              MailPoet.Notice.setMessage(message);
          }.bind(this.element));

          return this;
      },
      isHTML: function(str) {
          var a = document.createElement('div');
          a.innerHTML = str;
          for(var c = a.childNodes, i = c.length; i--;) {
              if(c[i].nodeType == 1) return true;
          }
          return false;
      },
      setMessage: function(message) {
          // if it's not an html message, let's sugar coat the message with a fancy <p>
          if(this.isHTML(message) === false) {
              message = '<p>'+message+'</p>';
          }
          // set message
          return this.element.html(message);
      },
      show: function(options) {
        // initialize
        this.init(options);

          // show notice
          this.showNotice();

          // return this;
      },
      showNotice: function() {
          // set message
          this.setMessage(this.options.message);

          // position notice
          this.element.insertAfter(jQuery('h2.title'));

          // set class name
          switch(this.options.type) {
            case 'success':
              this.element.addClass('updated');
            break;
            case 'system':
              this.element.addClass('update-nag');
            break;
            case 'error':
              this.element.addClass('error');
            break;
          }

          // make the notice appear
          this.element.fadeIn(200);

          // if scroll option is enabled, scroll to the notice
          if(this.options.scroll === true) {
              this.element.get(0).scrollIntoView(false);
          }

          // if the notice is not static, it has to disappear after a timeout
          if(this.options.static === false) {
              this.element.delay(this.options.timeout).trigger('close');
          } else if (this.options.hideClose === false) {
              this.element.append('<a href="javascript:;" class="mailpoet_notice_close"><span class="dashicons dashicons-dismiss"></span></a>');
              this.element.find('.mailpoet_notice_close').on('click', function() {
                  jQuery(this).trigger('close');
              });
          }

          // call onOpen callback
          if(this.options.onOpen !== null) {
              this.options.onOpen(this.element);
          }
      },
      hide: function(all) {
          if(all !== undefined && all === true) {
              jQuery('.mailpoet_notice:not([id])').trigger('close');
          } else if (all !== undefined && jQuery.isArray(all)) {
              for (var id in all) {
                  jQuery('[data-id="notice_' + all[id] + '"]')
                   .trigger('close');
              }
          } if (all !== undefined) {
              jQuery('[data-id="notice_' + all + '"]')
               .trigger('close');
          } else {
              jQuery('.mailpoet_notice.updated:not([id]), .mailpoet_notice.error:not([id])')
              .trigger('close');
          }
      },
      error: function(message, options) {
        this.show(jQuery.extend({}, {
              type: 'error',
              message: '<p>'+this.formatMessage(message)+'</p>'
          }, options));
      },
      success: function(message, options) {
          this.show(jQuery.extend({}, {
              type: 'success',
              message: '<p>'+this.formatMessage(message)+'</p>'
          }, options));
      },
      system: function(message, options) {
          this.show(jQuery.extend({}, {
              type: 'system',
              static: true,
              message: '<p>'+this.formatMessage(message)+'</p>'
          }, options));
      },
      formatMessage: function(message) {
        if(Array.isArray(message)) {
          return message.join('<br />');
        } else {
          return message;
        }
      }
  };
});
