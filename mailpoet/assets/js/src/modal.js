/* eslint-disable func-names */
import jQuery from 'jquery';
import Handlebars from 'handlebars';
import { __ } from '@wordpress/i18n';

var closeModalImage =
  '<svg viewBox="0 0 23 23" xmlns="http://www.w3.org/2000/svg">' +
  '<path d="M21.454 1.546L1.546 21.454M1.546 1.546L21.454 21.454" stroke-width="3" stroke-linecap="round" />' +
  '</svg>';

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

export const MailPoetModal = {
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
    overlay:
      '<div id="mailpoet_modal_overlay" class="mailpoet_modal_overlay" style="display:none;"></div>',
    popup:
      '<div id="mailpoet_popup" class="mailpoet_popup" tabindex="-1">' +
      '<div class="mailpoet_popup_wrapper">' +
      '<a href="javascript:;" id="mailpoet_modal_close" class="mailpoet_modal_close">' +
      closeModalImage +
      '</a>' +
      '<div id="mailpoet_popup_title" class="mailpoet_popup_title"><h2 class="mailpoet-h2"></h2></div>' +
      '<div class="mailpoet_popup_body clearfix"></div>' +
      '</div>' +
      '</div>',
    loading:
      '<div id="mailpoet_loading" class="mailpoet_loading" style="display:none;">' +
      '<div id="mailpoet_modal_loading_1" class="mailpoet_modal_loading mailpoet_modal_loading_1"></div>' +
      '<div id="mailpoet_modal_loading_2" class="mailpoet_modal_loading mailpoet_modal_loading_2"></div>' +
      '<div id="mailpoet_modal_loading_3" class="mailpoet_modal_loading mailpoet_modal_loading_3"></div>' +
      '</div>',
    progress:
      '<div id="mailpoet_modal_progress" class="mailpoet_modal_progress" style="display:none;">' +
      '<div class="mailpoet_modal_progress_bar" style="width: 0%"></div>' +
      '<div class="mailpoet_modal_progress_text">0%</div>' +
      '</div>',
    panel:
      '<div id="mailpoet_panel" class="mailpoet_panel">' +
      '<a href="javascript:;" id="mailpoet_modal_close" class="mailpoet_modal_close">' +
      closeModalImage +
      '</a>' +
      '<div class="mailpoet_panel_wrapper" tabindex="-1">' +
      '<div class="mailpoet_panel_body clearfix"></div>' +
      '</div>' +
      '</div>',
    subpanel:
      '<div class="mailpoet_panel_wrapper" tabindex="-1">' +
      '<div class="mailpoet_panel_body clearfix"></div>' +
      '</div>',
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
      return function () {
        return template;
      };
    }
    return Handlebars.compile(template);
  },
  init: function (options) {
    var modal;
    var $accessiblePopup;
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
        modal = this.compileTemplate(this.templates[this.options.type]);
        // create modal
        jQuery('#mailpoet_modal_overlay').append(modal(this.options));
        // set title
        jQuery('#mailpoet_popup').toggleClass(
          'mailpoet_popup_has_title',
          Boolean(this.options.title),
        );
        jQuery('#mailpoet_popup_title h2').html(this.options.title);

        // accessibility: dialog role and label
        $accessiblePopup = jQuery('#mailpoet_popup');
        $accessiblePopup.attr({ role: 'dialog', 'aria-modal': 'true' });
        if (this.options.title) {
          $accessiblePopup.removeAttr('aria-label');
          $accessiblePopup.attr('aria-labelledby', 'mailpoet_popup_title');
        } else {
          $accessiblePopup.removeAttr('aria-labelledby');
          $accessiblePopup.attr('aria-label', __('Dialog', 'mailpoet'));
        }
        jQuery('#mailpoet_modal_close').attr(
          'aria-label',
          __('Close', 'mailpoet'),
        );
      } else if (this.options.type === 'panel') {
        // create panel
        jQuery('#mailpoet_modal_overlay').after(
          this.templates[this.options.type],
        );
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
    this.options.body_template = this.compileTemplate(this.options.template);

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
      // insert progress indicator into overlay
      jQuery('#mailpoet_modal_overlay').append(this.templates.progress);
    }
    return this;
  },
  setOverlay: function (showBackground) {
    if (showBackground === true) {
      jQuery('#mailpoet_modal_overlay').removeClass(
        'mailpoet_overlay_transparent',
      );
    } else {
      jQuery('#mailpoet_modal_overlay').addClass(
        'mailpoet_overlay_transparent',
      );
    }
    return this;
  },
  setupEvents: function () {
    // close popup when user clicks on close button
    jQuery('#mailpoet_modal_close').on('click', this.cancel.bind(this));

    // close popup when user clicks on overlay
    jQuery('#mailpoet_modal_overlay').on(
      'click',
      function (e) {
        // we need to make sure that we are actually clicking on the overlay
        // because when clicking on the popup content, it will trigger
        // the click event on the overlay
        if (e.target.id === 'mailpoet_modal_overlay') {
          this.cancel();
        }
      }.bind(this),
    );

    // close popup when user presses ESC key
    jQuery(document).on(
      'keyup.mailpoet_modal',
      function (e) {
        if (this.opened === false) {
          return false;
        }
        if (e.keyCode === 27) {
          this.cancel();
        }
        return true;
      }.bind(this),
    );

    // make sure the popup is repositioned when the window is resized
    jQuery(window).on(
      'resize.mailpoet_modal',
      function () {
        this.setPosition();
      }.bind(this),
    );

    // trap keyboard focus inside the popup
    jQuery(document).on(
      'keydown.mailpoet_modal',
      function (e) {
        if (this.opened === false || this.options.type !== 'popup') {
          return true;
        }
        if (e.key === 'Tab' || e.keyCode === 9) {
          this.handleTabTrap(e);
        }
        return true;
      }.bind(this),
    );

    return this;
  },
  removeEvents: function () {
    jQuery(document).off('keyup.mailpoet_modal');
    jQuery(document).off('keydown.mailpoet_modal');
    jQuery(window).off('resize.mailpoet_modal');
    jQuery('#mailpoet_modal_close').off('click');
    if (this.options.overlay === true) {
      jQuery('#mailpoet_modal_overlay').off('click');
    }

    return this;
  },
  isSingleElementVisible: function (el) {
    // offsetParent/getClientRects rely on layout, which jsdom (our test
    // environment) never computes, so they always read as hidden there.
    // Computed style is layout-independent and works in both browsers and jsdom.
    var style = window.getComputedStyle(el);
    if (el.hidden) {
      return false;
    }
    if (!style) {
      return true;
    }
    return style.display !== 'none' && style.visibility !== 'hidden';
  },
  isElementVisible: function (el, boundary) {
    // A hidden ancestor (e.g. a conditionally jQuery.hide()'d wrapper) hides
    // an element even though the element's own computed style looks visible,
    // so walk up to the popup boundary checking every ancestor too.
    var node = el;
    while (node && node !== boundary) {
      if (!this.isSingleElementVisible(node)) {
        return false;
      }
      node = node.parentElement;
    }
    return true;
  },
  getFocusableElements: function () {
    var selector =
      'a[href], button:not([disabled]), ' +
      'input:not([disabled]):not([type="hidden"]), ' +
      'select:not([disabled]), textarea:not([disabled]), ' +
      '[tabindex]:not([tabindex="-1"])';
    var popup = document.getElementById('mailpoet_popup');
    var elements;
    var self = this;
    if (!popup) {
      return [];
    }
    elements = Array.prototype.slice.call(popup.querySelectorAll(selector));
    return elements.filter(function (el) {
      return self.isElementVisible(el, popup);
    });
  },
  handleTabTrap: function (e) {
    var popup = document.getElementById('mailpoet_popup');
    var target = e.target;
    var isInsidePopup;
    var focusableOutside;
    var focusableElements;
    var first;
    var last;

    if (!popup) {
      return;
    }

    isInsidePopup = popup.contains(target);

    if (!isInsidePopup) {
      if (target && target.closest && target.closest('.media-modal')) {
        return;
      }
      focusableOutside = this.getFocusableElements();
      e.preventDefault();
      if (focusableOutside.length > 0) {
        focusableOutside[0].focus();
      } else {
        popup.focus();
      }
      return;
    }

    focusableElements = this.getFocusableElements();
    if (focusableElements.length === 0) {
      e.preventDefault();
      popup.focus();
      return;
    }

    first = focusableElements[0];
    last = focusableElements[focusableElements.length - 1];

    // When focus sits on the popup container itself (no initialFocus was
    // set), it isn't one of the focusable elements inside it, so it never
    // matches `first`/`last` below — trap it explicitly in both directions.
    if (target === popup) {
      e.preventDefault();
      if (e.shiftKey) {
        last.focus();
      } else {
        first.focus();
      }
      return;
    }

    if (e.shiftKey) {
      if (target === first) {
        e.preventDefault();
        last.focus();
      }
    } else if (target === last) {
      e.preventDefault();
      first.focus();
    }
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
      jQuery('#mailpoet_' + this.options.type).append(this.templates.subpanel);

      // add sub panel content
      jQuery('.mailpoet_' + this.options.type + '_body')
        .last()
        .html(this.subpanels[this.subpanels.length - 1].element);

      // focus on sub panel
      if (this.options.focus) {
        this.focus();
      }
    } else if (this.options.element) {
      jQuery('.mailpoet_' + this.options.type + '_body').empty();
      jQuery('.mailpoet_' + this.options.type + '_body').append(
        this.options.element,
      );
    } else {
      jQuery('.mailpoet_' + this.options.type + '_body').html(
        this.options.body_template(this.options.data),
      );
    }

    return this;
  },
  loadUrl: function () {
    if (this.options.method === 'get') {
      // make ajax request
      jQuery.getJSON(
        this.options.url,
        function (data) {
          this.options.data = jQuery.extend({}, this.options.data, data);
          // load template using fetched data
          this.loadTemplate();
          // show modal window
          this.showModal();
        }.bind(this),
      );
    } else if (this.options.method === 'post') {
      // make ajax request
      jQuery.post(
        this.options.url,
        JSON.stringify(this.options.params),
        function (data) {
          this.options.data = jQuery.extend({}, this.options.data, data);
          // load template using fetched data
          this.loadTemplate();
          // show modal window
          this.showModal();
        }.bind(this),
        'json',
      );
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
      default:
        throw new Error('Incorrect type');
    }

    return this;
  },
  setPosition: function () {
    switch (this.options.type) {
      case 'popup':
        break;
      case 'panel':
        setTimeout(
          function () {
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
          }.bind(this),
          0,
        );
        break;
      default:
        throw new Error('Incorrect type');
    }

    return this;
  },
  showModal: function () {
    // set modal dimensions
    this.setDimensions();

    // remember the previously focused element (popup captures this earlier,
    // in popup(), so the opener isn't lost when init() closes a prior modal)
    if (this.options.type !== 'popup') {
      this.prevFocus = jQuery(':focus');
    }

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
    var $popup;
    var $initial;
    var focused;
    if (this.options.type === 'popup') {
      $popup = jQuery('#mailpoet_popup');
      focused = false;
      if (this.options.initialFocus) {
        $initial = $popup.find(this.options.initialFocus);
        if ($initial.length > 0) {
          $initial.trigger('focus');
          focused = true;
        }
      }
      if (!focused) {
        $popup.trigger('focus');
      }
    } else {
      // panel and subpanel
      jQuery('#mailpoet_' + this.options.type + ' .mailpoet_panel_wrapper')
        .filter(':visible')
        .trigger('focus');
    }
    return this;
  },
  highlightOn: function (element) {
    jQuery(element).addClass('mailpoet_modal_highlight');
    return this;
  },
  highlightOff: function () {
    jQuery('.mailpoet_modal_highlight').removeClass('mailpoet_modal_highlight');
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
    var $body = jQuery('body');
    // Used to add space which was took by scrollbar when it's hidden by overflow:hidden
    var bodyInnerWidth = $body.innerWidth();
    if (!this.options.overlayRender) {
      return this;
    }
    jQuery('#mailpoet_modal_overlay').show();
    jQuery('body')
      .addClass('mailpoet_modal_opened')
      .css('marginRight', $body.innerWidth() - bodyInnerWidth);
    return this;
  },
  hideOverlay: function () {
    jQuery('#mailpoet_modal_overlay').hide();
    jQuery('body').removeClass('mailpoet_modal_opened').css('marginRight', 0);
    return this;
  },
  popup: function (opts) {
    // get options
    var options = opts || {};
    // capture the currently focused element before init() can close
    // (and restore focus for) a previously open modal
    var activeElementBeforeInit = document.activeElement;
    // set modal type
    options.type = 'popup';
    // set overlay state
    options.overlay = options.overlay || true;
    // initialize modal
    this.init(options);
    // remember it as the element to restore focus to on close
    this.prevFocus = activeElementBeforeInit;
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
    jQuery('#mailpoet_modal_overlay').addClass('mailpoet_overlay_loading');

    return this;
  },
  hideLoading: function () {
    jQuery('#mailpoet_loading').hide();

    // remove loading class from overlay
    jQuery('#mailpoet_modal_overlay').removeClass('mailpoet_overlay_loading');

    return this;
  },
  progress: function (toggle) {
    // make sure the overlay is initialized and that it's visible
    this.initOverlay(true);

    if (toggle === true) {
      this.showProgress();
    } else {
      this.hideProgress();
    }

    return this;
  },
  showProgress: function () {
    this.setProgress(0);
    jQuery('#mailpoet_modal_progress').show();

    // add loading class to overlay
    jQuery('#mailpoet_modal_overlay').addClass('mailpoet_overlay_loading');

    return this;
  },
  hideProgress: function () {
    jQuery('#mailpoet_modal_progress').hide();

    // remove loading class from overlay
    jQuery('#mailpoet_modal_overlay').removeClass('mailpoet_overlay_loading');

    return this;
  },
  setProgress: function (percent) {
    var value = Math.max(0, Math.min(percent, 100));
    var $progress = jQuery('#mailpoet_modal_progress');
    $progress.find('.mailpoet_modal_progress_bar').css({ width: value + '%' });
    $progress.find('.mailpoet_modal_progress_text').text(value + '%');

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
      if (this.subpanels[this.subpanels.length - 1].onSuccess !== undefined) {
        this.subpanels[this.subpanels.length - 1].onSuccess(
          this.subpanels[this.subpanels.length - 1].data,
        );
      }
    } else if (this.options.onSuccess !== null) {
      this.options.onSuccess(this.options.data);
    }
    this.close();

    return this;
  },
  cancel: function () {
    if (this.subpanels.length > 0) {
      if (this.subpanels[this.subpanels.length - 1].onCancel !== undefined) {
        this.subpanels[this.subpanels.length - 1].onCancel(
          this.subpanels[this.subpanels.length - 1].data,
        );
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
    // read focus-restoration state before destroy()/options reset touch it
    var returnFocusOption = this.options.returnFocus;
    var prevFocusValue = this.prevFocus;
    var modalType = this.options.type;

    if (this.isLocked() === true) {
      return this;
    }

    if (this.subpanels.length > 0) {
      // close subpanel
      jQuery('.mailpoet_' + this.options.type + '_wrapper')
        .last()
        .remove();

      // show previous panel
      jQuery('.mailpoet_' + this.options.type + '_wrapper')
        .last()
        .show();

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

    // restore focus to the opener, a requested target, or the page heading
    this.restoreFocus(prevFocusValue, returnFocusOption, modalType);

    // reset options
    this.options = {
      onSuccess: null,
      onCancel: null,
    };
    this.prevFocus = null;

    return this;
  },
  toFocusableElement: function (value) {
    if (!value) {
      return null;
    }
    if (value instanceof Element) {
      return value;
    }
    // support the jQuery-wrapped values used for panels
    if (value.jquery && value.length > 0) {
      return value.get(0);
    }
    return null;
  },
  resolveReturnFocus: function (returnFocus) {
    if (!returnFocus) {
      return null;
    }
    if (typeof returnFocus === 'function') {
      return this.toFocusableElement(returnFocus());
    }
    if (typeof returnFocus === 'string') {
      return document.querySelector(returnFocus);
    }
    return this.toFocusableElement(returnFocus);
  },
  restoreFocus: function (prevFocusValue, returnFocusOption, modalType) {
    var prevFocusElement = this.toFocusableElement(prevFocusValue);
    var returnFocusElement;
    var heading;

    if (prevFocusElement && document.contains(prevFocusElement)) {
      prevFocusElement.focus();
      return;
    }

    // The returnFocus -> page heading fallback chain is a popup-only
    // feature. Panels/subpanels (legacy editor side panels) keep their
    // original behaviour: restore prevFocus if present, else do nothing.
    if (modalType !== 'popup') {
      return;
    }

    returnFocusElement = this.resolveReturnFocus(returnFocusOption);
    if (returnFocusElement && document.contains(returnFocusElement)) {
      returnFocusElement.focus();
      return;
    }

    heading = document.querySelector('.wrap h1');
    if (heading) {
      if (!heading.hasAttribute('tabindex')) {
        heading.setAttribute('tabindex', '-1');
      }
      heading.focus();
    }
  },
};
