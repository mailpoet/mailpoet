define('newsletter_editor/components/sidebar', [
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
    'sticky-kit',
  ], function(EditorApplication, Backbone, Marionette) {

  EditorApplication.module("components.sidebar", function(Module, App, Backbone, Marionette, $, _) {
      "use strict";

      // Widget handlers for use to create new content blocks via drag&drop
      Module._contentWidgets = new (Backbone.Collection.extend({
          model: Backbone.SuperModel.extend({
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
          model: Backbone.SuperModel.extend({
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

      var SidebarView = Backbone.Marionette.LayoutView.extend({
          getTemplate: function() { return templates.sidebar; },
          regions: {
              contentRegion: '.mailpoet_content_region',
              layoutRegion: '.mailpoet_layout_region',
              stylesRegion: '.mailpoet_styles_region',
              previewRegion: '.mailpoet_preview_region',
          },
          events: {
              'click .mailpoet_sidebar_region h3, .mailpoet_sidebar_region .handlediv': function(event) {
                  this.$el.find('.mailpoet_sidebar_region').addClass('closed');
                  this.$el.find(event.target).parent().parent().removeClass('closed');
              },
          },
          initialize: function(options) {
              $(window)
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

                  self = $(this);

                  if (self.css('position') === 'fixed') {
                      calculated_left = self.parent().offset().left - $(window).scrollLeft();
                      self.css('left', calculated_left + 'px');
                  } else {
                      self.css('left', '');
                  }
              });
          },
          onDomRefresh: function() {
              var that = this;
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
      Module.SidebarWidgetsView = Backbone.Marionette.CompositeView.extend({
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
      Module.SidebarStylesView = Backbone.Marionette.LayoutView.extend({
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
                  "change #mailpoet_newsletter_background_color": _.partial(this.changeColorField, 'newsletter.backgroundColor'),
                  "change #mailpoet_background_color": _.partial(this.changeColorField, 'background.backgroundColor'),
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
              var that = this;
          },
          onRender: function() {
              var that = this;
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

      Module.SidebarPreviewView = Backbone.Marionette.LayoutView.extend({
          getTemplate: function() { return templates.sidebarPreview; },
          events: {
              'click .mailpoet_show_preview': 'showPreview',
              'click #mailpoet_send_preview': 'sendPreview',
          },
          showPreview: function() {
              var json = App.toJSON();

              mailpoet_post_json('newsletter_render.php', { data: json }, function(response) {
                  console.log('Should open a new window');
                  window.open('data:text/html,' + encodeURIComponent(response), '_blank');
              }, function(error) {
                  console.log('Preview error', json);
                  alert('Something went wrong, check console');
              });
          },
          sendPreview: function() {
              // testing sending method
              console.log('trying to send a preview');
              // get form data
              var data = {
                  from_name: this.$('#mailpoet_preview_from_name').val(),
                  from_email: this.$('#mailpoet_preview_from_email').val(),
                  to_email: this.$('#mailpoet_preview_to_email').val(),
                  newsletter: App.newsletterId,
              };

              // send test email
              MailPoet.Modal.loading(true);

              // TODO: Migrate logic to new AJAX format
              //mailpoet_post_wpi('newsletter_preview.php', data, function(response) {
                  //if(response.success !== undefined && response.success === true) {
                      //MailPoet.Notice.success(App.getConfig().get('translations.testEmailSent'));
                  //} else if(response.error !== undefined) {
                      //if(response.error.length === 0) {
                          //MailPoet.Notice.error(App.getConfig().get('translations.unknownErrorOccurred'));
                      //} else {
                          //$(response.error).each(function(i, error) {
                              //MailPoet.Notice.error(error);
                          //});
                      //}
                  //}
                  //MailPoet.Modal.loading(false);
              //}, function(error) {
                  //// an error occurred
                  //MailPoet.Modal.loading(false);
              //});
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
  });

});
