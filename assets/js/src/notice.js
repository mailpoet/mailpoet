'use strict';

define('notice', ['mailpoet', 'jquery'], function (mp, jQuery) { // eslint-disable-line func-names
  /*= ==========================================================================================

      MailPoet Notice:

          description: Handles notices
          version: 1.0
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

  =========================================================================================== */
  var MailPoet = mp;
  MailPoet.Notice = {
    version: 1.0,
    // default options
    defaults: {
      type: 'success',
      message: '',
      static: false,
      hideClose: false,
      id: null,
      positionAfter: false,
      scroll: false,
      timeout: 5000,
      onOpen: null,
      onClose: null
    },
    options: {},
    init: function init(options) {
      // set options
      this.options = jQuery.extend({}, this.defaults, options);

      return this;
    },
    createNotice: function createNotice() {
      var onClose;
      var positionAfter;
      // clone element
      this.element = jQuery('#mailpoet_notice_' + this.options.type).clone();

      // add data-id to the element
      if (this.options.id) {
        this.element.attr(
          'data-id',
          this.options.id
        );
      }

      // remove id from clone
      this.element.removeAttr('id');

      // insert notice after its parent
      if (typeof this.options.positionAfter === 'object') {
        positionAfter = this.options.positionAfter;
      } else if (typeof this.options.positionAfter === 'string') {
        positionAfter = jQuery(this.options.positionAfter);
      } else {
        positionAfter = jQuery('#mailpoet_notice_' + this.options.type);
      }
      positionAfter.after(this.element);

      // setup onClose callback
      onClose = null;
      if (this.options.onClose !== null) {
        onClose = this.options.onClose;
      }

      // listen to remove event
      jQuery(this.element).on('close', function () { // eslint-disable-line func-names
        jQuery(this).fadeOut(200, function () { // eslint-disable-line func-names
          // on close callback
          if (onClose !== null) {
            onClose();
          }
          // remove notice
          jQuery(this).remove();
        });
      }.bind(this.element));

      // listen to message event
      jQuery(this.element).on('setMessage', function (e, message) { // eslint-disable-line func-names
        MailPoet.Notice.setMessage(message);
      });

      return this;
    },
    updateNotice: function updateNotice() {
      // update notice's message
      jQuery('[data-id="' + this.options.id + '"').first().trigger(
        'setMessage', this.options.message
      );
    },
    setMessage: function setMessage(message) {
      var formattedMessage = this.formatMessage(message);

      // let's sugar coat the message with a fancy <p>
      formattedMessage = '<p>' + formattedMessage + '</p>';
      // set message
      return this.element.html(formattedMessage);
    },
    formatMessage: function formatMessage(message) {
      if (Array.isArray(message)) {
        return message.join('<br />');
      }
      return message;
    },
    show: function show(options) {
      // initialize
      this.init(options);

      if (
        this.options.id !== null
        &&
        jQuery('[data-id="' + this.options.id + '"]').length > 0
      ) {
        this.updateNotice();
      } else {
        this.createNotice();
      }
      this.showNotice();
    },
    showNotice: function showNotice() {
      // set message
      this.setMessage(this.options.message);

      // position notice
      this.element.insertAfter(jQuery('h2.title'));

      // set class name
      switch (this.options.type) {
        case 'success':
          this.element.addClass('notice notice-success');
          break;
        case 'system':
          this.element.addClass('notice notice-warning');
          break;
        case 'error':
          this.element.addClass('notice notice-error');
          break;
      }

      // make the notice appear
      this.element.fadeIn(200);

      // if scroll option is enabled, scroll to the notice
      if (this.options.scroll === true) {
        this.element.get(0).scrollIntoView(false);
      }

      // if the notice is not static, it has to disappear after a timeout
      if (this.options.static === false) {
        this.element.delay(this.options.timeout).trigger('close');
      } else if (this.options.hideClose === false) {
        this.element.append('<a href="javascript:;" class="mailpoet_notice_close"><span class="dashicons dashicons-dismiss"></span></a>');
        this.element.find('.mailpoet_notice_close').on('click', function () { // eslint-disable-line func-names
          jQuery(this).trigger('close');
        });
      }

      // call onOpen callback
      if (this.options.onOpen !== null) {
        this.options.onOpen(this.element);
      }
    },
    hide: function hide(all) {
      var id;
      if (all !== undefined && all === true) {
        // all notices
        jQuery('.mailpoet_notice:not([id])').trigger('close');
      } else if (all !== undefined && jQuery.isArray(all)) {
        // array of ids
        for (id in all) {
          jQuery('[data-id="' + all[id] + '"]').trigger('close');
        }
      } if (all !== undefined) {
        // single id
        jQuery('[data-id="' + all + '"]').trigger('close');
      } else {
        jQuery('.mailpoet_notice.notice-success:not([id]), .mailpoet_notice.notice-error:not([id])')
          .trigger('close');
      }
    },
    error: function error(message, options) {
      this.show(jQuery.extend({}, {
        type: 'error',
        message: message
      }, options));
    },
    success: function success(message, options) {
      this.show(jQuery.extend({}, {
        type: 'success',
        message: message
      }, options));
    },
    system: function system(message, options) {
      this.show(jQuery.extend({}, {
        type: 'system',
        static: true,
        message: message
      }, options));
    }
  };
});
