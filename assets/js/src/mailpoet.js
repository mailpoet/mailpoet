webpackJsonp([1],[
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	__webpack_require__(1);
	__webpack_require__(11);
	__webpack_require__(12);
	module.exports = __webpack_require__(13);


/***/ },
/* 1 */,
/* 2 */,
/* 3 */,
/* 4 */,
/* 5 */,
/* 6 */,
/* 7 */,
/* 8 */,
/* 9 */,
/* 10 */,
/* 11 */
/***/ function(module, exports, __webpack_require__) {

	var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(1), __webpack_require__(2)], __WEBPACK_AMD_DEFINE_RESULT__ = function(MailPoet, jQuery) {
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
	}.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__), __WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ },
/* 12 */
/***/ function(module, exports, __webpack_require__) {

	var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(1), __webpack_require__(2)], __WEBPACK_AMD_DEFINE_RESULT__ = function(MailPoet, jQuery) {
	  "use strict";
	  /*==================================================================================================

	      MailPoet Modal:

	          version: 0.8
	          author: Jonathan Labreuille
	          company: Wysija
	          dependencies: jQuery


	      Options:

	          Mandatory:
	              // Modal window's title
	              (string) title: 'Modal title'

	              // template
	              (string) template:  jQuery('#handlebars_template').html() or
	                                  literal html

	          Optional:
	              // jQuery cached element object node to be displayed,
	              // instead of creating a new one
	              (object) element: jQuery(selector)

	              // - data object that will be passed to the template when rendering
	              (object) data: {},

	              // - data will be loaded via this url and passed to the template
	              // when rendering
	              // - if a "data" option was specified, it will be merged with the
	              // ajax's response data
	              (string) url: '/url.json'

	              // ajax method
	              (string) method: 'post' (default: 'get')

	              // ajax post params
	              (object) params: {}

	              // - integers are expressed in pixels
	              (mixed) width: '50%' | 100 | '100px'

	              // - integers are expressed in pixels
	              // - will be ignored when in "panel" mode
	              (mixed) height: '50%' | 100 | '100px'

	              // - only used for "panel" mode
	              // - will be ignored in "popup" mode
	              (string) position: 'left' | 'right'

	              // display overlay or not
	              (boolean) overlay: true | false

	              // element(s) to be highlighted when the overlay is "on"
	              (object) highlight: jQuery element

	              // callbacks
	              (function) onInit: called when the modal is displayed
	              (function) onSuccess: called by calling MailPoet_Guide.success()
	              (function) onCancel: called when closing the popup
	                                   or by calling MailPoet_Guide.cancel()

	      Usage:

	          // popup mode
	          MailPoet.Modal.popup(options);

	          // panel mode
	          MailPoet.Modal.panel(options);

	          // loading states
	          MailPoet.Modal.loading(true); // displays loading indicator
	          MailPoet.Modal.loading(false); // hides loading indicator

	  ==================================================================================================*/

	  MailPoet.Modal = {
	      version: 0.8,

	      // flags
	      initialized: false,
	      opened: false,
	      locked: false,

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
	          overlay: false,

	          // highlighted elements
	          highlight: null,

	          // callbacks
	          onInit: null,
	          onSuccess: null,
	          onCancel: null
	      },
	      renderer: 'html',
	      options: {},
	      templates: {
	          overlay: '<div id="mailpoet_modal_overlay" style="display:none;"></div>',
	          popup: '<div id="mailpoet_popup">'+
	                      '<div class="mailpoet_popup_wrapper">'+
	                          '<a href="javascript:;" id="mailpoet_modal_close"></a>'+
	                          '<div id="mailpoet_popup_title"><h2></h2></div>'+
	                          '<div class="mailpoet_popup_body clearfix"></div>'+
	                      '</div>'+
	                  '</div>',
	          loading: '<div id="mailpoet_loading" style="display:none;">'+
	                      '<div id="mailpoet_modal_loading_1" class="mailpoet_modal_loading"></div>'+
	                      '<div id="mailpoet_modal_loading_2" class="mailpoet_modal_loading"></div>'+
	                      '<div id="mailpoet_modal_loading_3" class="mailpoet_modal_loading"></div>'+
	                  '</div>',
	          panel: '<div id="mailpoet_panel">'+
	                      '<a href="javascript:;" id="mailpoet_modal_close"></a>'+
	                      '<div class="mailpoet_panel_wrapper">'+
	                          '<div class="mailpoet_panel_body clearfix"></div>'+
	                      '</div>'+
	                  '</div>',
	          subpanel: '<div class="mailpoet_panel_wrapper">'+
	                      '<div class="mailpoet_panel_body clearfix"></div>'+
	                  '</div>'
	      },
	      setRenderer: function() {
	          this.renderer = (typeof(Handlebars) === "undefined") ? 'html' : 'handlebars';
	      },
	      compileTemplate: function(template) {
	          if(this.renderer === 'html') {
	              return function() { return template; };
	          } else {
	              return Handlebars.compile(template);
	          }
	      },
	      init: function(options) {
	          if(this.initialized === true) {
	              this.close();
	          }

	          // merge options
	          this.options = jQuery.extend({}, this.defaults, options);

	          // set renderer
	          this.setRenderer();

	          // init overlay
	          this.initOverlay();

	          // toggle overlay
	          this.toggleOverlay(this.options.overlay);

	          if(this.options.type !== null) {
	              // insert modal depending on its type
	              if(this.options.type === 'popup') {
	                  var modal = this.compileTemplate(this.templates[this.options.type]);
	                  // create modal
	                  jQuery('#mailpoet_modal_overlay').append(modal(this.options));
	                  // set title
	                  jQuery('#mailpoet_popup_title h2').html(this.options.title);
	              } else if(this.options.type === 'panel') {
	                  // create panel
	                  jQuery('#mailpoet_modal_overlay').after(this.templates[this.options.type]);
	              }

	              // add proper overlay class
	              jQuery('#mailpoet_modal_overlay')
	                  .removeClass('mailpoet_popup_overlay mailpoet_panel_overlay')
	                  .addClass('mailpoet_'+this.options.type+'_overlay');
	          }

	          // render template if specified
	          if(this.options.template !== null) {
	              // set "success" callback if specified
	              if(options.onSuccess !== undefined) {
	                  this.options.onSuccess = options.onSuccess;
	              }

	              // set "cancel" callback if specified
	              if(options.onCancel !== undefined) {
	                  this.options.onCancel = options.onCancel;
	              }

	              // compile template
	              this.options.body_template = this.compileTemplate(this.options.template);

	              // setup events
	              this.setupEvents();
	          }

	          // set popup as initialized
	          this.initialized = true;

	          return this;
	      },
	      initOverlay: function(toggle) {
	          if(jQuery('#mailpoet_modal_overlay').length === 0) {
	              // insert overlay into the DOM
	              jQuery('body').append(this.templates.overlay);
	              // insert loading indicator into overlay
	              jQuery('#mailpoet_modal_overlay').append(this.templates.loading);
	          }
	          return this;
	      },
	      toggleOverlay: function(toggle) {
	          if(toggle === true) {
	              jQuery('#mailpoet_modal_overlay').removeClass('mailpoet_overlay_hidden');
	          } else {
	              jQuery('#mailpoet_modal_overlay').addClass('mailpoet_overlay_hidden');
	          }

	          return this;
	      },
	      setupEvents: function() {
	          // close popup when user clicks on close button
	          jQuery('#mailpoet_modal_close').on('click', this.cancel.bind(this));

	          // close popup when user clicks on overlay
	          jQuery('#mailpoet_modal_overlay').on('click', function(e) {
	              // we need to make sure that we are actually clicking on the overlay
	              // because when clicking on the popup content, it will trigger the click
	              // event on the overlay
	              if(e.target.id === 'mailpoet_modal_overlay') { this.cancel(); }
	          }.bind(this));

	          // close popup when user presses ESC key
	          jQuery(document).on('keyup.mailpoet_modal', function(e) {
	              if(this.opened === false) { return false; }
	              if(e.keyCode === 27) { this.cancel(); }
	          }.bind(this));

	          // make sure the popup is repositioned when the window is resized
	          jQuery(window).on('resize.mailpoet_modal', function() {
	              this.setPosition();
	          }.bind(this));

	          return this;
	      },
	      removeEvents: function() {
	          jQuery(document).unbind('keyup.mailpoet_modal');
	          jQuery(window).unbind('resize.mailpoet_modal');
	          jQuery('#mailpoet_modal_close').off('click');
	          if(this.options.overlay === true) {
	              jQuery('#mailpoet_modal_overlay').off('click');
	          }

	          return this;
	      },
	      lock: function() {
	          this.locked = true;

	          return this;
	      },
	      unlock: function() {
	          this.locked = false;

	          return this;
	      },
	      isLocked: function() {
	          return this.locked;
	      },
	      loadTemplate: function() {
	          if(this.subpanels.length > 0) {
	              // hide panel
	              jQuery('.mailpoet_'+this.options.type+'_wrapper').hide();

	              // add sub panel wrapper
	              jQuery('#mailpoet_'+this.options.type).append(this.templates['subpanel']);

	              // add sub panel content
	              jQuery('.mailpoet_'+this.options.type+'_body').last().html(this.subpanels[(this.subpanels.length - 1)].element);
	          } else if (this.options.element) {
	              jQuery('.mailpoet_'+this.options.type+'_body').empty();
	              jQuery('.mailpoet_'+this.options.type+'_body').append(this.options.element);
	          } else {
	              jQuery('.mailpoet_'+this.options.type+'_body').html(
	                  this.options.body_template(
	                      this.options.data
	                  )
	              );
	          }

	          return this;
	      },
	      loadUrl: function() {
	          if(this.options.method === 'get') {
	              // make ajax request
	              jQuery.getJSON(this.options.url, function(data) {
	                  // merge returned data with existing data passed when calling the "open" method
	                  this.options.data = jQuery.extend({}, this.options.data, data);
	                  // load template using fetched data
	                  this.loadTemplate();
	                  // show modal window
	                  this.showModal();
	              }.bind(this));
	          } else if(this.options.method === 'post') {
	              // make ajax request
	              jQuery.post(this.options.url, JSON.stringify(this.options.params), function(data) {
	                  // merge returned data with existing data passed when calling the "open" method
	                  this.options.data = jQuery.extend({}, this.options.data, data);
	                  // load template using fetched data
	                  this.loadTemplate();
	                  // show modal window
	                  this.showModal();
	              }.bind(this), 'json');
	          }

	          return this;
	      },
	      setDimensions: function() {
	          switch(this.options.type) {
	              case 'popup':
	                  // set popup dimensions
	                  jQuery('#mailpoet_popup').css({
	                      width: this.options.width,
	                      minHeight: this.options.height
	                  });
	                  // set popup wrapper height
	                  jQuery('#mailpoet_popup_wrapper').css({ height: this.options.height});
	              break;
	              case 'panel':
	                  // set dimensions
	                  if(this.options.position === 'right') {
	                      jQuery('#mailpoet_panel').css({
	                          width: this.options.width,
	                          right: 0,
	                          marginRight: '-' + this.options.width,
	                          left: 'auto'
	                      });
	                  } else if(this.options.position === 'left') {
	                      jQuery('#mailpoet_panel').css({
	                          width: this.options.width,
	                          left: 0,
	                          marginLeft: '-' + this.options.width,
	                          right: 'auto'
	                      });
	                  }
	                  jQuery('#mailpoet_panel').css({ minHeight: 'auto' });
	              break;
	          }

	          return this;
	      },
	      setPosition: function() {
	          switch(this.options.type) {
	              case 'popup':
	                  var screenWidth = jQuery(window).width(),
	                      screenHeight = jQuery(window).height(),
	                      modalWidth = jQuery('.mailpoet_'+ this.options.type +'_wrapper').width(),
	                      modalHeight = jQuery('.mailpoet_'+ this.options.type +'_wrapper').height();

	                  var top = Math.max(48, parseInt((screenHeight / 2) - (modalHeight / 2))),
	                      left = Math.max(0, parseInt((screenWidth / 2) - (modalWidth / 2)));

	                  // set position of popup depending on screen dimensions.
	                  jQuery('#mailpoet_popup').css({
	                      top: top,
	                      left: left
	                  });
	              break;
	              case 'panel':
	                  setTimeout(function() {
	                      // set position of popup depending on screen dimensions.
	                      if(this.options.position === 'right') {
	                          jQuery('#mailpoet_panel').css(
	                              { marginRight: 0 }
	                          );
	                      } else if(this.options.position === 'left') {
	                          jQuery('#mailpoet_panel').css(
	                              { marginLeft: 0 }
	                          );
	                      }
	                  }.bind(this), 0);
	              break;
	          }

	          return this;
	      },
	      showModal: function() {
	          // set modal dimensions
	          this.setDimensions();

	          // add a flag on the body so that we can prevent scrolling (setting overflow hidden)
	          jQuery('body').addClass('mailpoet_modal_opened');

	          // show popup
	          jQuery('#mailpoet_'+this.options.type).show();

	          // display overlay
	          this.showOverlay();

	          // set modal position
	          this.setPosition();

	          // add class on highlighted elements
	          if(this.options.highlight !== null) {
	              if(this.options.highlight.length > 0) {
	                  this.highlightOn(this.options.highlight);
	              }
	          }

	          // set popup as opened
	          this.opened = true;

	          // trigger init event if specified
	          if(this.options.onInit !== null) {
	              this.options.onInit();
	          }

	          return this;
	      },
	      highlightOn: function(element) {
	          jQuery(element).addClass('mailpoet_modal_highlight');
	          return this;
	      },
	      highlightOff: function() {
	          jQuery('.mailpoet_modal_highlight').removeClass('mailpoet_modal_highlight');
	          return this;
	      },
	      hideModal: function(callback) {
	          // set modal as closed
	          this.opened = false;

	          // hide modal
	          jQuery('#mailpoet_'+this.options.type).hide();

	           // remove class on highlighted elements
	          this.highlightOff();

	          // remove class from body to let it be scrollable
	          jQuery('body').removeClass('mailpoet_modal_opened');

	          return this;
	      },
	      showOverlay: function(force) {
	          jQuery('#mailpoet_modal_overlay').show();
	          return this;
	      },
	      hideOverlay: function() {
	          jQuery('#mailpoet_modal_overlay').hide();
	          return this;
	      },
	      popup: function(options) {
	          // get options
	          options = options || {};
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
	      panel: function(options) {
	          // get options
	          options = options || {};
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
	      subpanel: function(options) {
	          if(this.opened === false) {
	              // if no panel is already opened, let's create one instead
	              this.panel(options);
	          } else {
	              // if a panel is already opened, add a sub panel to it
	              this.subpanels.push(options);
	              this.loadTemplate();
	          }

	          return this;
	      },
	      loading: function(toggle) {
	          // make sure the overlay is initialized and that it's visible
	          this.initOverlay(true);

	          if(toggle === true) {
	              this.showLoading();
	          } else {
	              this.hideLoading();
	          }

	          return this;
	      },
	      showLoading: function() {
	          jQuery('#mailpoet_loading').show();

	          // add loading class to overlay
	          jQuery('#mailpoet_modal_overlay').addClass('mailpoet_overlay_loading');

	          return this;
	      },
	      hideLoading: function() {
	          jQuery('#mailpoet_loading').hide();

	          // remove loading class from overlay
	          jQuery('#mailpoet_modal_overlay').removeClass('mailpoet_overlay_loading');

	          return this;
	      },
	      open: function() {
	          // load template if specified
	          if(this.options.template !== null) {
	              // check if a url was specified to get extra data
	              if(this.options.url !== null) {
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
	      success: function() {
	          if(this.subpanels.length > 0) {
	              if(this.subpanels[(this.subpanels.length - 1)].onSuccess !== undefined) {
	                  this.subpanels[(this.subpanels.length - 1)].onSuccess(this.subpanels[(this.subpanels.length - 1)].data);
	              }
	          } else {
	              if(this.options.onSuccess !== null) {
	                  this.options.onSuccess(this.options.data);
	              }
	          }
	          this.close();

	          return this;
	      },
	      cancel: function() {
	          if(this.subpanels.length > 0) {
	              if(this.subpanels[(this.subpanels.length - 1)].onCancel !== undefined) {
	                  this.subpanels[(this.subpanels.length - 1)].onCancel(this.subpanels[(this.subpanels.length - 1)].data);
	              }
	          } else {
	              if(this.options.onCancel !== null) {
	                  this.options.onCancel(this.options.data);
	              }
	          }
	          this.close();

	          return this;
	      },
	      destroy: function() {
	          this.hideOverlay();

	          // remove extra modal
	          if(jQuery('#mailpoet_'+this.options.type).length > 0) {
	              jQuery('#mailpoet_'+this.options.type).remove();
	          }

	          this.initialized = false;

	          return this;
	      },
	      close: function() {
	          if(this.isLocked() === true) return this;

	          if(this.subpanels.length > 0) {

	              // close subpanel
	              jQuery('.mailpoet_'+this.options.type+'_wrapper').last().remove();

	              // show previous panel
	              jQuery('.mailpoet_'+this.options.type+'_wrapper').last().show();

	              // remove last subpanels
	              this.subpanels.pop();

	              return this;
	          }

	          // remove event handlers
	          this.removeEvents();

	          // hide modal window
	          this.hideModal();

	          // destroy modal element
	          this.destroy();

	          // reset options
	          this.options = {
	              onSuccess: null,
	              onCancel: null
	          };

	          return this;
	      }
	  };
	}.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__), __WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ },
/* 13 */
/***/ function(module, exports, __webpack_require__) {

	var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(1), __webpack_require__(2)], __WEBPACK_AMD_DEFINE_RESULT__ = function(MailPoet, jQuery) {
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

	          // remove id from clone
	          this.element.removeAttr('id');

	          // insert notice after its parent
	          jQuery('#mailpoet_notice_'+this.options.type).after(this.element);

	          // setup onClose callback
	          var onClose = null;
	          if(this.options.onClose !== null) {
	              onClose = this.options.onClose;
	          }

	          // listen to remove event
	          var element = this.element;
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

	          // make the notice appear
	          this.element.fadeIn(200);

	          // if scroll option is enabled, scroll to the notice
	          if(this.options.scroll === true) {
	              this.element.get(0).scrollIntoView(false);
	          }

	          // if the notice is not static, it has to disappear after a timeout
	          if(this.options.static === false) {
	              this.element.delay(this.options.timeout).trigger('close');
	          } else {
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
	          } else {
	              jQuery('.mailpoet_notice.updated:not([id]), .mailpoet_notice.error:not([id])')
	              .trigger('close');
	          }
	      },
	      error: function(message, options) {
	        this.show(jQuery.extend({}, {
	              type: 'error',
	              message: '<p>'+message+'</p>'
	          }, options));
	      },
	      success: function(message, options) {
	          this.show(jQuery.extend({}, {
	              type: 'success',
	              message: '<p>'+message+'</p>'
	          }, options));
	      },
	      system: function(message, options) {
	          this.show(jQuery.extend({}, {
	              type: 'system',
	              static: true,
	              message: message
	          }, options));
	      }
	  };
	}.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__), __WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }
]);