define([
    'newsletter_editor/App',
    'newsletter_editor/components/communication',
    'mailpoet',
    'backbone',
    'backbone.marionette',
    'backbone.supermodel',
    'underscore',
    'jquery',
    'sticky-kit'
  ], function(
    App,
    CommunicationComponent,
    MailPoet,
    Backbone,
    Marionette,
    SuperModel,
    _,
    jQuery,
    StickyKit
  ) {

  "use strict";

  var Module = {};

  // Widget handlers for use to create new content blocks via drag&drop
  Module._contentWidgets = new (Backbone.Collection.extend({
    model: SuperModel.extend({
      defaults: {
        name: '',
        priority: 100,
        widgetView: undefined,
      },
    }),
    comparator: 'priority',
  }))();
  Module.registerWidget = function(widget) { return Module._contentWidgets.add(widget); };
  Module.getWidgets = function() { return Module._contentWidgets; };

  // Layout widget handlers for use to create new layout blocks via drag&drop
  Module._layoutWidgets = new (Backbone.Collection.extend({
    model: SuperModel.extend({
      defaults: {
        name: '',
        priority: 100,
        widgetView: undefined,
      },
    }),
    comparator: 'priority',
  }))();
  Module.registerLayoutWidget = function(widget) { return Module._layoutWidgets.add(widget); };
  Module.getLayoutWidgets = function() { return Module._layoutWidgets; };

  var SidebarView = Marionette.LayoutView.extend({
    getTemplate: function() { return templates.sidebar; },
    regions: {
      contentRegion: '.mailpoet_content_region',
      layoutRegion: '.mailpoet_layout_region',
      stylesRegion: '.mailpoet_styles_region',
      previewRegion: '.mailpoet_preview_region',
    },
    events: {
      'click .mailpoet_sidebar_region h3, .mailpoet_sidebar_region .handlediv': function(event) {
        var $openRegion = this.$el.find('.mailpoet_sidebar_region:not(.closed)'),
          $targetRegion = this.$el.find(event.target).closest('.mailpoet_sidebar_region');

        $openRegion.find('.mailpoet_region_content').velocity(
          'slideUp',
          {
            duration: 250,
            easing: "easeOut",
            complete: function() {
              $openRegion.addClass('closed');
            }.bind(this)
          }
        );

        if ($openRegion.get(0) !== $targetRegion.get(0)) {
          $targetRegion.find('.mailpoet_region_content').velocity(
            'slideDown',
            {
              duration: 250,
              easing: "easeIn",
              complete: function() {
                $targetRegion.removeClass('closed');
              },
            }
          );
        }
      },
    },
    initialize: function(options) {
      jQuery(window)
        .on('resize', this.updateHorizontalScroll.bind(this))
        .on('scroll', this.updateHorizontalScroll.bind(this));
    },
    onRender: function() {
      this.contentRegion.show(new Module.SidebarWidgetsView({
        collection: App.getWidgets(),
      }));
      this.layoutRegion.show(new Module.SidebarLayoutWidgetsView({
        collection: App.getLayoutWidgets(),
      }));
      this.stylesRegion.show(new Module.SidebarStylesView({
        model: App.getGlobalStyles(),
        availableStyles: App.getAvailableStyles(),
      }));
      this.previewRegion.show(new Module.SidebarPreviewView());
    },
    updateHorizontalScroll: function() {
      // Fixes the sidebar so that on narrower screens the horizontal
      // position of the sidebar would be scrollable and not fixed
      // partially out of visible screen
      this.$el.parent().each(function () {
        var calculated_left, self;

        self = jQuery(this);

        if (self.css('position') === 'fixed') {
          calculated_left = self.parent().offset().left - jQuery(window).scrollLeft();
          self.css('left', calculated_left + 'px');
        } else {
          self.css('left', '');
        }
      });
    },
    onDomRefresh: function() {
      this.$el.parent().stick_in_parent({
        offset_top: 32,
      });
      this.$el.parent().on('sticky_kit:stick', this.updateHorizontalScroll.bind(this));
      this.$el.parent().on('sticky_kit:unstick', this.updateHorizontalScroll.bind(this));
      this.$el.parent().on('sticky_kit:bottom', this.updateHorizontalScroll.bind(this));
      this.$el.parent().on('sticky_kit:unbottom', this.updateHorizontalScroll.bind(this));
    },
  });

  /**
   * Responsible for rendering draggable content widgets
   */
  Module.SidebarWidgetsView = Marionette.CompositeView.extend({
    getTemplate: function() { return templates.sidebarContent; },
    getChildView: function(model) {
      return model.get('widgetView');
    },
    childViewContainer: '.mailpoet_region_content',
  });

  /**
   * Responsible for rendering draggable layout widgets
   */
  Module.SidebarLayoutWidgetsView = Module.SidebarWidgetsView.extend({
    getTemplate: function() { return templates.sidebarLayout; },
  });
  /**
   * Responsible for managing global styles
   */
  Module.SidebarStylesView = Marionette.LayoutView.extend({
    getTemplate: function() { return templates.sidebarStyles; },
    events: function() {
      return {
        "change #mailpoet_text_font_color": _.partial(this.changeColorField, 'text.fontColor'),
        "change #mailpoet_text_font_family": function(event) {
          this.model.set('text.fontFamily', event.target.value);
        },
        "change #mailpoet_text_font_size": function(event) {
          this.model.set('text.fontSize', event.target.value);
        },
        "change #mailpoet_h1_font_color": _.partial(this.changeColorField, 'h1.fontColor'),
        "change #mailpoet_h1_font_family": function(event) {
          this.model.set('h1.fontFamily', event.target.value);
        },
        "change #mailpoet_h1_font_size": function(event) {
          this.model.set('h1.fontSize', event.target.value);
        },
        "change #mailpoet_h2_font_color": _.partial(this.changeColorField, 'h2.fontColor'),
        "change #mailpoet_h2_font_family": function(event) {
          this.model.set('h2.fontFamily', event.target.value);
        },
        "change #mailpoet_h2_font_size": function(event) {
          this.model.set('h2.fontSize', event.target.value);
        },
        "change #mailpoet_h3_font_color": _.partial(this.changeColorField, 'h3.fontColor'),
        "change #mailpoet_h3_font_family": function(event) {
          this.model.set('h3.fontFamily', event.target.value);
        },
        "change #mailpoet_h3_font_size": function(event) {
          this.model.set('h3.fontSize', event.target.value);
        },
        "change #mailpoet_a_font_color": _.partial(this.changeColorField, 'link.fontColor'),
        "change #mailpoet_a_font_underline": function(event) {
          this.model.set('link.textDecoration', (event.target.checked) ? event.target.value : 'none');
        },
        "change #mailpoet_newsletter_background_color": _.partial(this.changeColorField, 'wrapper.backgroundColor'),
        "change #mailpoet_background_color": _.partial(this.changeColorField, 'body.backgroundColor'),
      };
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
        availableStyles: this.availableStyles.toJSON(),
      };
    },
    initialize: function(options) {
      this.availableStyles = options.availableStyles;
    },
    onRender: function() {
      this.$('.mailpoet_color').spectrum({
        clickoutFiresChange: true,
        showInput: true,
        showInitial: true,
        preferredFormat: "hex6",
        allowEmpty: true,
      });
    },
    changeField: function(field, event) {
      this.model.set(field, jQuery(event.target).val());
    },
    changeColorField: function(field, event) {
      var value = jQuery(event.target).val();
      if (value === '') {
        value = 'transparent';
      }
      this.model.set(field, value);
    },
  });

  Module.SidebarPreviewView = Marionette.LayoutView.extend({
    getTemplate: function() { return templates.sidebarPreview; },
    events: {
      'click .mailpoet_show_preview': 'showPreview',
      'click #mailpoet_send_preview': 'sendPreview',
    },
    showPreview: function() {
      var json = App.toJSON();

      // Stringify to enable transmission of primitive non-string value types
      if (!_.isUndefined(json.body)) {
        json.body = JSON.stringify(json.body);
      }

      MailPoet.Modal.loading(true);

      MailPoet.Ajax.post({
        endpoint: 'newsletters',
        action: 'showPreview',
        data: json,
      }).done(function(response){
        MailPoet.Modal.loading(false);

        if (response.result === true) {
          window.open(response.data.url, '_blank')
        }
        MailPoet.Notice.error(response.errors);
      }).fail(function(error) {
        MailPoet.Modal.loading(false);
        MailPoet.Notice.error(
          MailPoet.I18n.t('newsletterPreviewFailed')
        );
      });
    },
    sendPreview: function() {
      // get form data
      var $emailField = this.$('#mailpoet_preview_to_email');
      var data = {
        subscriber: $emailField.val(),
        id: App.getNewsletter().get('id'),
      };

      if (data.subscriber.length <= 0) {
        MailPoet.Notice.error(
          MailPoet.I18n.t('newsletterPreviewEmailMissing'),
          {
            positionAfter: $emailField,
            scroll: true,
          }
        );
        return false;
      }

      // send test email
      MailPoet.Modal.loading(true);

      CommunicationComponent.previewNewsletter(data).done(function(response) {
        if(response.result !== undefined && response.result === true) {
          MailPoet.Notice.success(MailPoet.I18n.t('newsletterPreviewSent'), { scroll: true });
        } else {
          if (_.isArray(response.errors)) {
            response.errors.map(function(error) {
              MailPoet.Notice.error(error, { scroll: true });
            });
          } else {
            MailPoet.Notice.error(
              MailPoet.I18n.t('newsletterPreviewFailedToSend'),
              {
                scroll: true,
                static: true,
              }
            );
          }
        }
        MailPoet.Modal.loading(false);
      }).fail(function(response) {
        // an error occurred
        MailPoet.Modal.loading(false);
      });
    },
  });

  App.on('before:start', function(options) {
    App.registerWidget = Module.registerWidget;
    App.getWidgets = Module.getWidgets;
    App.registerLayoutWidget = Module.registerLayoutWidget;
    App.getLayoutWidgets = Module.getLayoutWidgets;
  });

  App.on('start', function(options) {
    var stylesModel = App.getGlobalStyles(),
      sidebarView = new SidebarView();

    App._appView.sidebarRegion.show(sidebarView);
  });

  return Module;
});
