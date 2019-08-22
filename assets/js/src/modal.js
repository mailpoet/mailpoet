/* eslint-disable func-names */
import mp from 'mailpoet';
import jQuery from 'jquery';

var MailPoet = mp;
var closeModalImage = '<svg viewBox="0 0 23 23" xmlns="http://www.w3.org/2000/svg">'
        + '<path d="M21.454 1.546L1.546 21.454M1.546 1.546L21.454 21.454" stroke-width="3" stroke-linecap="round" />'
      + '</svg>';

/** *************************************************************************
    MailPoet Modal:

      version: 0.9
      author: Jonathan Labreuille
      company: Wysija
      dependencies: jQuery

    Usage:
      // popup mode
      MailPoet.Modal.popup(options);

      // panel mode
      MailPoet.Modal.panel(options);

      // loading mode
      MailPoet.Modal.loading(bool);
    ************************************************************************** */

MailPoet.Modal = {
  version: 0.9,

  // flags
  initialized: false,
  opened: false,
  locked: false,

  // previously focused element
  prevFocus: null,

  // sub panels
  subpanels: [],

  // default values
  defaults: {
    // title
    title: null,

    // type
    type: null,

    // positionning
    position: 'right',

    // data sources
    data: {},
    url: null,
    method: 'get',
    params: {},

    // template
    template: null,
    body_template: null,

    // dimensions
    width: 'auto',
    height: 'auto',

    // display overlay
    overlay: false, // Controls background of overlay, false means transparent
    overlayRender: true, // Controls display property, false means hidden

    // focus upon displaying
    focus: true,

    // highlighted elements
    highlight: null,

    // callbacks
    onInit: null,
    onSuccess: null,
    onCancel: null,
  },
  renderer: 'html',
  options: {},
  templates: {
    overlay: '<div id="mailpoet_modal_overlay" class="mailpoet_modal_overlay" style="display:none;"></div>',
    popup: '<div id="mailpoet_popup" class="mailpoet_popup" tabindex="-1">'
        + '<div class="mailpoet_popup_wrapper">'
        + '<a href="javascript:;" id="mailpoet_modal_close" class="mailpoet_modal_close">' + closeModalImage + '</a>'
        + '<div id="mailpoet_popup_title" class="mailpoet_popup_title"><h2></h2></div>'
        + '<div class="mailpoet_popup_body clearfix"></div>'
        + '</div>'
        + '</div>',
    loading: '<div id="mailpoet_loading" class="mailpoet_loading_bird_container" style="display:none;">'
        + '<svg class="mailpoet_loading_bird" viewBox="0 0 205 165" xmlns="http://www.w3.org/2000/svg">'
        + '    <path class="mailpoet_loading_bird_back" d="M93.72,65.32S85.65,28.68,102.94,1.1L115,52" fill="#0a0951" />'
        + '    <path d="M190.68,63.8c-5.67-2-25.64-9.43-34.29-9.59,0,0-11.42-21.61-31.17-12.61a27,27,0,0,0-12,7.76c-4.91,5.81-11.74,6.86-17.88,13.36C87.53,71,59.4,84.32,45,100.65c-1.08,1.23-2.09,2.47-3,3.74a138.55,138.55,0,0,1,21.56,11c21.5,8.74,32.8,8.09,38.75,5.36,14-6.39,32-26,26.67-48.52,0,0,15.77,1.25,21.91-13.87,5-.34,27.85,2.78,51,10.59A65.76,65.76,0,0,0,190.68,63.8Z" fill="#4366f7" />'
        + '    <path d="M155.08,52.09S188.29,60.58,202,69c0,0-42.83-12.18-51.3-10C151.08,57.6,153.36,54.57,155.08,52.09Z" fill="#0a0951" />'
        + '    <path d="M147,43.25c5.79,3.59,2,12.89-4,16-.16.08.25-.11.09,0-3.29,1.54-10.45,1.69-17.59,1.36-10.4-.48-15.38,8.62-21.83,16.79C93.79,89.87,78.31,94.33,78.31,94.33s.25-1.61,4.6-11.91C87.75,71,93,61.83,103.44,55c2.62-1.72,8.16-4.9,10.08-6.73C119.33,42.74,126.85,30.77,147,43.25Z" fill="#28169d" />'
        + '    <circle cx="138.6" cy="50.27" r="3.47" fill="#fff" />'
        + '    <circle cx="138.6" cy="50.16" r="2" fill="#28169d" />'
        + '    <path d="M125.92,83.64a12.39,12.39,0,0,1-3.5,9.66,19.71,19.71,0,0,1,.33-9.65" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M120.4,89.83s.67,5.71-3.5,9.66a19.66,19.66,0,0,1,.33-9.64" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M119.18,80.63a12.39,12.39,0,0,1-3.5,9.66,19.66,19.66,0,0,1,.33-9.64" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M109.08,102.66s.67,5.71-3.5,9.66a19.66,19.66,0,0,1,.33-9.64" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M114.18,93.34s.67,5.71-3.5,9.66a19.66,19.66,0,0,1,.33-9.64" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M118.74,101.09a11.34,11.34,0,0,1-5.55,7.58,18,18,0,0,1,2.76-8.38" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M124.94,95.36s-.36,5.73-5.16,8.88a19.5,19.5,0,0,1,2-9.42" fill="#6289fb" opacity="0.5" />'
        + '    <path d="M57.51,90.19S29.9,103.68,18.79,132l6,1.07s-7.1,6.63-3.6,9.67a27.87,27.87,0,0,0,7.58,4.55s10-32.9,40.51-29.89" fill="#4366f7" />'
        + '    <path d="M54.53,116.89s-24.39,48.93-26.38,42.92C27.06,156.5,38.68,128.38,48.76,120" fill="#4366f7" />'
        + '    <path d="M28.74,115.14S-3.83,152.86.37,155.58s29.28-32,29.28-32" fill="#4366f7" />'
        + '    <path d="M107.32,117.94s-13,10.33-44.9-.85c0,0-30.44-8.55-48.71,17.47,0,0,30.58-48.62,62.1-52.09C75.81,82.47,72.68,135,107.32,117.94Z" fill="#0a0951" opacity="0.6" />'
        + '    <path d="M93.77,64a149,149,0,0,1,16-12.38s9.12-21.95,31-12.41c0,0-21.82-3.94-29.62,14.76L94.67,63.74Z" fill="#fff" />'
        + '    <path class="mailpoet_loading_bird_front" d="M94.94,63.89,30.36,0s-5.19,2-2.58,9.1c2.44,6.57,4.13,8.3,4.62,8.15,0,0-5.69-3.43-8.22,2.2s11.37,10,11.37,10S20.21,26,22.74,32.28s12.05,9.2,12.05,9.2-10.43.81-8.39,6S37.78,53,37.78,53s-9.5,3.13-6.19,8c3.13,4.54,9.7,3.68,9.7,3.68s-8.9,2.56-5.8,6.35C39.73,76.16,49.3,73.8,49.3,73.8s-9.43,3.34-7.15,9.54c.75,2,12.12-2,12.12-2s-6.11,8.29-2.15,9.44,7.39-3,7.39-3,.58,5.74,3.92,8.49,11,1,14.79-3.56c0,0,20.09-2.87,27-20Z" fill="#0a0951" />'
        + '</svg>'
        + '</div>',
    panel: '<div id="mailpoet_panel" class="mailpoet_panel">'
        + '<a href="javascript:;" id="mailpoet_modal_close" class="mailpoet_modal_close">' + closeModalImage + '</a>'
        + '<div class="mailpoet_panel_wrapper" tabindex="-1">'
        + '<div class="mailpoet_panel_body clearfix"></div>'
        + '</div>'
        + '</div>',
    subpanel: '<div class="mailpoet_panel_wrapper" tabindex="-1">'
        + '<div class="mailpoet_panel_body clearfix"></div>'
        + '</div>',
  },
  getContentContainer: function () {
    return jQuery('.mailpoet_' + this.options.type + '_body');
  },
  setRenderer: function (renderer) {
    this.renderer = renderer;
    return this;
  },
  compileTemplate: function (template) {
    if (this.renderer === 'html') {
      return function () { return template; };
    }
    return window.Handlebars.compile(template);
  },
  init: function (options) {
    var modal;
    if (this.initialized === true) {
      this.close();
    }
    // merge options
    this.options = jQuery.extend({}, this.defaults, options);

    // set renderer
    this.setRenderer(this.options.renderer);

    // init overlay
    this.initOverlay();

    // toggle overlay
    this.setOverlay(this.options.overlay);

    if (this.options.type !== null) {
      // insert modal depending on its type
      if (this.options.type === 'popup') {
        modal = this.compileTemplate(
          this.templates[this.options.type]
        );
        // create modal
        jQuery('#mailpoet_modal_overlay')
          .append(modal(this.options));
        // set title
        jQuery('#mailpoet_popup')
          .toggleClass('mailpoet_popup_has_title', Boolean(this.options.title));
        jQuery('#mailpoet_popup_title h2')
          .html(this.options.title);
      } else if (this.options.type === 'panel') {
        // create panel
        jQuery('#mailpoet_modal_overlay')
          .after(this.templates[this.options.type]);
      }

      // add proper overlay class
      jQuery('#mailpoet_modal_overlay')
        .removeClass('mailpoet_popup_overlay mailpoet_panel_overlay')
        .addClass('mailpoet_' + this.options.type + '_overlay');
    }

    // set "success" callback if specified
    if (options.onSuccess !== undefined) {
      this.options.onSuccess = options.onSuccess;
    }

    // set "cancel" callback if specified
    if (options.onCancel !== undefined) {
      this.options.onCancel = options.onCancel;
    }

    // compile template
    this.options.body_template = this.compileTemplate(
      this.options.template
    );

    // setup events
    this.setupEvents();

    // set popup as initialized
    this.initialized = true;

    return this;
  },
  initOverlay: function () {
    if (jQuery('#mailpoet_modal_overlay').length === 0) {
      // insert overlay into the DOM
      jQuery('body').append(this.templates.overlay);
      // insert loading indicator into overlay
      jQuery('#mailpoet_modal_overlay').append(this.templates.loading);
    }
    return this;
  },
  setOverlay: function (showBackground) {
    if (showBackground === true) {
      jQuery('#mailpoet_modal_overlay')
        .removeClass('mailpoet_overlay_transparent');
    } else {
      jQuery('#mailpoet_modal_overlay')
        .addClass('mailpoet_overlay_transparent');
    }
    return this;
  },
  setupEvents: function () {
    // close popup when user clicks on close button
    jQuery('#mailpoet_modal_close').on('click', this.cancel.bind(this));

    // close popup when user clicks on overlay
    jQuery('#mailpoet_modal_overlay').on('click', function (e) {
      // we need to make sure that we are actually clicking on the overlay
      // because when clicking on the popup content, it will trigger
      // the click event on the overlay
      if (e.target.id === 'mailpoet_modal_overlay') { this.cancel(); }
    }.bind(this));

    // close popup when user presses ESC key
    jQuery(document).on('keyup.mailpoet_modal', function (e) {
      if (this.opened === false) { return false; }
      if (e.keyCode === 27) { this.cancel(); }
      return true;
    }.bind(this));

    // make sure the popup is repositioned when the window is resized
    jQuery(window).on('resize.mailpoet_modal', function () {
      this.setPosition();
    }.bind(this));

    return this;
  },
  removeEvents: function () {
    jQuery(document).unbind('keyup.mailpoet_modal');
    jQuery(window).unbind('resize.mailpoet_modal');
    jQuery('#mailpoet_modal_close').off('click');
    if (this.options.overlay === true) {
      jQuery('#mailpoet_modal_overlay').off('click');
    }

    return this;
  },
  lock: function () {
    this.locked = true;

    return this;
  },
  unlock: function () {
    this.locked = false;

    return this;
  },
  isLocked: function () {
    return this.locked;
  },
  loadTemplate: function () {
    if (this.subpanels.length > 0) {
      // hide panel
      jQuery('.mailpoet_' + this.options.type + '_wrapper').hide();

      // add sub panel wrapper
      jQuery('#mailpoet_' + this.options.type)
        .append(this.templates.subpanel);

      // add sub panel content
      jQuery('.mailpoet_' + this.options.type + '_body').last()
        .html(this.subpanels[(this.subpanels.length - 1)].element);

      // focus on sub panel
      if (this.options.focus) {
        this.focus();
      }
    } else if (this.options.element) {
      jQuery('.mailpoet_' + this.options.type + '_body').empty();
      jQuery('.mailpoet_' + this.options.type + '_body')
        .append(this.options.element);
    } else {
      jQuery('.mailpoet_' + this.options.type + '_body')
        .html(
          this.options.body_template(
            this.options.data
          )
        );
    }

    return this;
  },
  loadUrl: function () {
    if (this.options.method === 'get') {
      // make ajax request
      jQuery.getJSON(this.options.url,
        function (data) {
          this.options.data = jQuery.extend({}, this.options.data, data);
          // load template using fetched data
          this.loadTemplate();
          // show modal window
          this.showModal();
        }.bind(this));
    } else if (this.options.method === 'post') {
      // make ajax request
      jQuery.post(this.options.url, JSON.stringify(this.options.params),
        function (data) {
          this.options.data = jQuery.extend({}, this.options.data, data);
          // load template using fetched data
          this.loadTemplate();
          // show modal window
          this.showModal();
        }.bind(this),
        'json');
    }

    return this;
  },
  setDimensions: function () {
    switch (this.options.type) {
      case 'popup':
        // set popup dimensions
        if (this.options.width && this.options.width !== 'auto') {
          jQuery('#mailpoet_popup').css({
            width: this.options.width,
          });
        }

        if (this.options.height && this.options.height !== 'auto') {
          jQuery('#mailpoet_popup_wrapper').css({
            height: this.options.height,
          });
          jQuery('#mailpoet_popup').css({
            height: this.options.height,
          });
        }

        if (this.options.minWidth) {
          jQuery('#mailpoet_popup').css({ minWidth: this.options.minWidth });
        }
        break;
      case 'panel':
        // set dimensions
        if (this.options.position === 'right') {
          jQuery('#mailpoet_panel').css({
            width: this.options.width,
            right: 0,
            marginRight: '-' + this.options.width,
            left: 'auto',
          });
        } else if (this.options.position === 'left') {
          jQuery('#mailpoet_panel').css({
            width: this.options.width,
            left: 0,
            marginLeft: '-' + this.options.width,
            right: 'auto',
          });
        }
        jQuery('#mailpoet_panel').css({ minHeight: 'auto' });
        break;
      default: throw new Error('Incorrect type');
    }

    return this;
  },
  setPosition: function () {
    switch (this.options.type) {
      case 'popup':
        break;
      case 'panel':
        setTimeout(function () {
          // set position of popup depending on screen dimensions.
          if (this.options.position === 'right') {
            jQuery('#mailpoet_panel').css({
              marginRight: 0,
            });
          } else if (this.options.position === 'left') {
            jQuery('#mailpoet_panel').css({
              marginLeft: 0,
            });
          }
        }.bind(this), 0);
        break;
      default: throw new Error('Incorrect type');
    }

    return this;
  },
  showModal: function () {
    // set modal dimensions
    this.setDimensions();

    // remember the previously focused element
    this.prevFocus = jQuery(':focus');

    // show popup
    jQuery('#mailpoet_' + this.options.type).show();

    // display overlay
    this.showOverlay();

    // set modal position
    this.setPosition();

    // add class on highlighted elements
    if (this.options.highlight !== null) {
      if (this.options.highlight.length > 0) {
        this.highlightOn(this.options.highlight);
      }
    }

    if (this.options.focus) {
      this.focus();
    }

    // reset any possible scroll from previous modal
    jQuery('#mailpoet_modal_overlay').scrollTop(0);

    // set popup as opened
    this.opened = true;

    // trigger init event if specified
    if (this.options.onInit !== null) {
      this.options.onInit(this);
    }

    return this;
  },
  focus: function () {
    if (this.options.type === 'popup') {
      jQuery('#mailpoet_' + this.options.type).focus();
    } else {
      // panel and subpanel
      jQuery('#mailpoet_' + this.options.type + ' .mailpoet_panel_wrapper')
        .filter(':visible').focus();
    }
    return this;
  },
  highlightOn: function (element) {
    jQuery(element).addClass('mailpoet_modal_highlight');
    return this;
  },
  highlightOff: function () {
    jQuery('.mailpoet_modal_highlight')
      .removeClass('mailpoet_modal_highlight');
    return this;
  },
  hideModal: function () {
    // set modal as closed
    this.opened = false;

    // hide modal
    jQuery('#mailpoet_' + this.options.type).hide();

    // remove class on highlighted elements
    this.highlightOff();

    return this;
  },
  showOverlay: function () {
    if (!this.options.overlayRender) {
      return this;
    }
    jQuery('#mailpoet_modal_overlay').show();
    jQuery('body').addClass('mailpoet_modal_opened');
    return this;
  },
  hideOverlay: function () {
    jQuery('#mailpoet_modal_overlay').hide();
    jQuery('body').removeClass('mailpoet_modal_opened');
    return this;
  },
  popup: function (opts) {
    // get options
    var options = opts || {};
    // set modal type
    options.type = 'popup';
    // set overlay state
    options.overlay = options.overlay || true;
    // initialize modal
    this.init(options);
    // open modal
    this.open();

    return this;
  },
  panel: function (opts) {
    // get options
    var options = opts || {};
    // reset subpanels
    this.subpanels = [];
    // set modal type
    options.type = 'panel';
    // set overlay state
    options.overlay = options.overlay || false;
    // set highlighted element
    options.highlight = options.highlight || null;
    // set modal dimensions
    options.width = options.width || '40%';
    options.height = options.height || 'auto';
    // initialize modal
    this.init(options);
    // open modal
    this.open();

    return this;
  },
  subpanel: function (options) {
    if (this.opened === false) {
      // if no panel is already opened, let's create one instead
      this.panel(options);
    } else {
      // if a panel is already opened, add a sub panel to it
      this.subpanels.push(options);
      this.loadTemplate();
    }

    return this;
  },
  loading: function (toggle) {
    // make sure the overlay is initialized and that it's visible
    this.initOverlay(true);

    if (toggle === true) {
      this.showLoading();
    } else {
      this.hideLoading();
    }

    return this;
  },
  showLoading: function () {
    jQuery('#mailpoet_loading').show();

    // add loading class to overlay
    jQuery('#mailpoet_modal_overlay')
      .addClass('mailpoet_overlay_loading')
      .fadeIn(200);

    return this;
  },
  hideLoading: function () {
    jQuery('#mailpoet_loading').hide();

    // remove loading class from overlay
    jQuery('#mailpoet_modal_overlay')
      .removeClass('mailpoet_overlay_loading')
      .hide();

    return this;
  },
  open: function () {
    // load template if specified
    if (this.options.template !== null) {
      // check if a url was specified to get extra data
      if (this.options.url !== null) {
        this.loadUrl();
      } else {
        // load template
        this.loadTemplate();

        // show modal window
        this.showModal();
      }
    } else {
      this.cancel();
    }

    return this;
  },
  success: function () {
    if (this.subpanels.length > 0) {
      if (this.subpanels[(this.subpanels.length - 1)].onSuccess !== undefined) {
        this
          .subpanels[(this.subpanels.length - 1)]
          .onSuccess(this.subpanels[(this.subpanels.length - 1)].data);
      }
    } else if (this.options.onSuccess !== null) {
      this.options.onSuccess(this.options.data);
    }
    this.close();

    return this;
  },
  cancel: function () {
    if (this.subpanels.length > 0) {
      if (this.subpanels[(this.subpanels.length - 1)].onCancel !== undefined) {
        this
          .subpanels[(this.subpanels.length - 1)]
          .onCancel(this.subpanels[(this.subpanels.length - 1)].data);
      }
    } else if (this.options.onCancel !== null) {
      this.options.onCancel(this.options.data);
    }
    this.close();

    return this;
  },
  destroy: function () {
    this.hideOverlay();

    // remove extra modal
    if (jQuery('#mailpoet_' + this.options.type).length > 0) {
      jQuery('#mailpoet_' + this.options.type).remove();
    }

    this.initialized = false;

    return this;
  },
  close: function () {
    if (this.isLocked() === true) { return this; }

    if (this.subpanels.length > 0) {
      // close subpanel
      jQuery('.mailpoet_' + this.options.type + '_wrapper').last().remove();

      // show previous panel
      jQuery('.mailpoet_' + this.options.type + '_wrapper').last().show();

      // remove last subpanels
      this.subpanels.pop();

      // focus on previous panel
      if (this.options.focus) {
        this.focus();
      }

      return this;
    }

    // remove event handlers
    this.removeEvents();

    // hide modal window
    this.hideModal();

    // destroy modal element
    this.destroy();

    // restore the previously focused element
    if (this.prevFocus !== undefined) {
      this.prevFocus.focus();
    }

    // reset options
    this.options = {
      onSuccess: null,
      onCancel: null,
    };

    return this;
  },
};
