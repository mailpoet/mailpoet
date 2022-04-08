import { MailPoetI18n as I18n } from './i18n';
import jQuery from 'jquery'; // eslint-disable-line func-names

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
export const MailPoetNotice = {
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
    timeout: 10000,
    onOpen: null,
    onClose: null,
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
    jQuery(this.element).on(
      'close',
      // eslint-disable-next-line func-names
      function () {
        // eslint-disable-next-line func-names
        jQuery(this).fadeOut(200, function () {
          // on close callback
          if (onClose !== null) {
            onClose();
          }
          // remove notice
          jQuery(this).remove();
        });
    }.bind(this.element));

    return this;
  },
  updateNotice: function updateNotice() {
    // update notice's message
    this.setMessage(
      this.options.message,
      jQuery('[data-id="' + this.options.id + '"').first()
    );
  },
  setMessage: function setMessage(message, element) {
    var formattedMessage = this.formatMessage(message);
    var el = element || this.element;

    // let's sugar coat the message with a fancy <p>
    formattedMessage = '<p>' + formattedMessage + '</p>';
    // set message
    return el.html(formattedMessage);
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
        && jQuery('[data-id="' + this.options.id + '"]').length > 0
    ) {
      this.updateNotice();
    } else {
      this.createNotice();
      this.showNotice();
    }
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
      default:
        this.element.addClass('notice');
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
      setTimeout(
        // eslint-disable-next-line func-names
        function (target) {
         target.trigger('close');
        },
        this.options.timeout, this.element
      );
    }

    if (this.options.hideClose === false) {
      this.element.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
      // eslint-disable-next-line func-names
      this.element.find('.notice-dismiss').on('click', function () {
        jQuery(this).trigger('close');
      });
    }

    // call onOpen callback
    if (this.options.onOpen !== null) {
      this.options.onOpen(this.element);
    }
  },
  hide: function hide(all) {
    if (all !== undefined && all === true) {
      // all notices
      jQuery('.mailpoet_notice:not([id])').trigger('close');
    } else if (all !== undefined && Array.isArray(all)) {
      // array of ids
      Object.keys(all).forEach(function close(id) {
        jQuery('[data-id="' + all[id] + '"]').trigger('close');
      });
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
      message: message,
    }, options));
  },
  success: function success(message, options) {
    this.show(jQuery.extend({}, {
      type: 'success',
      message: message,
    }, options));
  },
  system: function system(message, options) {
    this.show(jQuery.extend({}, {
      type: 'system',
      static: true,
      message: message,
    }, options));
  },
  showApiErrorNotice: function showApiErrorNotice(response, options) {
    var errorMessage = I18n.t('ajaxFailedErrorMessage');
    if (response && response.errors && response.errors.length > 0) {
      errorMessage = response.errors.map(error => error.message);
    }
    this.show(jQuery.extend({}, {
      type: 'error',
      message: errorMessage,
    }, options));
  },
};
