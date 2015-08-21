/**
 * Button content block
 */
define('newsletter_editor/blocks/button', [
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
    'mailpoet',
  ], function(EditorApplication, Backbone, Marionette, MailPoet) {

  EditorApplication.module("blocks.button", function(Module, App, Backbone, Marionette, $, _) {
      "use strict";

      var base = App.module('blocks.base');

      Module.ButtonBlockModel = base.BlockModel.extend({
          defaults: function() {
              return this._getDefaults({
                  type: 'button',
                  text: 'Button',
                  url: 'http://google.com',
                  styles: {
                      block: {
                          backgroundColor: '#ff0000',
                          borderColor: '#cccccc',
                          borderWidth: '1px',
                          borderRadius: '4px',
                          borderStyle: 'solid',
                          width: '200px',
                          lineHeight: '40px',
                          fontColor: '#000000',
                          fontFamily: 'Arial',
                          fontSize: '16px',
                          textAlign: 'center',
                      },
                  },
              }, EditorApplication.getConfig().get('blockDefaults.button'));
          },
      });

      Module.ButtonBlockView = base.BlockView.extend({
          className: "mailpoet_block mailpoet_button_block mailpoet_droppable_block",
          getTemplate: function() { return templates.buttonBlock; },
          modelEvents: {
              'change': 'render',
          },
          onDragSubstituteBy: function() { return Module.ButtonWidgetView; },
          initialize: function() {
              base.BlockView.prototype.initialize.apply(this, arguments);
              var that = this;

              // Listen for attempts to change all dividers in one go
              this._replaceButtonStylesHandler = function(data) { that.model.set(data); };
              App.getChannel().on('replaceAllButtonStyles', this._replaceButtonStylesHandler);
          },
          onRender: function() {
              this.toolsView = new Module.ButtonBlockToolsView({ model: this.model });
              this.toolsRegion.show(this.toolsView);
          },
      });

      Module.ButtonBlockToolsView = base.BlockToolsView.extend({
          getSettingsView: function() { return Module.ButtonBlockSettingsView; },
      });

      Module.ButtonBlockSettingsView = base.BlockSettingsView.extend({
          getTemplate: function() { return templates.buttonBlockSettings; },
          events: function() {
              return {
                  "keyup .mailpoet_field_button_text": _.partial(this.changeField, "text"),
                  "keyup .mailpoet_field_button_url": _.partial(this.changeField, "url"),
                  "change .mailpoet_field_button_alignment": _.partial(this.changeField, "styles.block.textAlign"),
                  "change .mailpoet_field_button_font_color": _.partial(this.changeColorField, "styles.block.fontColor"),
                  "change .mailpoet_field_button_font_family": _.partial(this.changeField, "styles.block.fontFamily"),
                  "change .mailpoet_field_button_font_size": _.partial(this.changeField, "styles.block.fontSize"),
                  "change .mailpoet_field_button_background_color": _.partial(this.changeColorField, "styles.block.backgroundColor"),
                  "change .mailpoet_field_button_border_color": _.partial(this.changeColorField, "styles.block.borderColor"),

                  "input .mailpoet_field_button_border_width": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width_input', _.partial(this.changePixelField, "styles.block.borderWidth").bind(this)),
                  "change .mailpoet_field_button_border_width": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width_input', _.partial(this.changePixelField, "styles.block.borderWidth").bind(this)),
                  "change .mailpoet_field_button_border_width_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width', _.partial(this.changePixelField, "styles.block.borderWidth").bind(this)),
                  "keyup .mailpoet_field_button_border_width_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width', _.partial(this.changePixelField, "styles.block.borderWidth").bind(this)),

                  "input .mailpoet_field_button_border_radius": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius_input', _.partial(this.changePixelField, "styles.block.borderRadius").bind(this)),
                  "change .mailpoet_field_button_border_radius": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius_input', _.partial(this.changePixelField, "styles.block.borderRadius").bind(this)),
                  "change .mailpoet_field_button_border_radius_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius', _.partial(this.changePixelField, "styles.block.borderRadius").bind(this)),
                  "keyup .mailpoet_field_button_border_radius_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius', _.partial(this.changePixelField, "styles.block.borderRadius").bind(this)),

                  "input .mailpoet_field_button_width": _.partial(this.updateValueAndCall, '.mailpoet_field_button_width_input', _.partial(this.changePixelField, "styles.block.width").bind(this)),
                  "change .mailpoet_field_button_width": _.partial(this.updateValueAndCall, '.mailpoet_field_button_width_input', _.partial(this.changePixelField, "styles.block.width").bind(this)),
                  "change .mailpoet_field_button_width_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_width', _.partial(this.changePixelField, "styles.block.width").bind(this)),
                  "keyup .mailpoet_field_button_width_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_width', _.partial(this.changePixelField, "styles.block.width").bind(this)),

                  "input .mailpoet_field_button_line_height": _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height_input', _.partial(this.changePixelField, "styles.block.lineHeight").bind(this)),
                  "change .mailpoet_field_button_line_height": _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height_input', _.partial(this.changePixelField, "styles.block.lineHeight").bind(this)),
                  "change .mailpoet_field_button_line_height_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height', _.partial(this.changePixelField, "styles.block.lineHeight").bind(this)),
                  "keyup .mailpoet_field_button_line_height_input": _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height', _.partial(this.changePixelField, "styles.block.lineHeight").bind(this)),

                  "click .mailpoet_field_button_replace_all_styles": "applyToAll",
                  "click .mailpoet_done_editing": "close",
              };
          },
          behaviors: {
              ColorPickerBehavior: {},
          },
          initialize: function(params) {
              var panelParams = {
                  element: this.$el,
                  template: '',
                  position: 'right',
                  width: App.getConfig().get('sidepanelWidth'),
              };
              this.renderOptions = params.renderOptions || {};
              if (this.renderOptions.displayFormat === 'subpanel') {
                  MailPoet.Modal.subpanel(panelParams);
              } else {
                  MailPoet.Modal.panel(panelParams);
              }
          },
          templateHelpers: function() {
              return {
                  model: this.model.toJSON(),
                  availableStyles: App.getAvailableStyles().toJSON(),
                  renderOptions: this.renderOptions,
              };
          },
          applyToAll: function() {
              App.getChannel().trigger('replaceAllButtonStyles', _.pick(this.model.toJSON(), 'styles', 'type'));
          },
          updateValueAndCall: function(fieldToUpdate, callable, event) {
              this.$(fieldToUpdate).val(jQuery(event.target).val());
              callable(event);
          },
      });

      Module.ButtonWidgetView = base.WidgetView.extend({
          getTemplate: function() { return templates.buttonInsertion; },
          behaviors: {
              DraggableBehavior: {
                  cloneOriginal: true,
                  drop: function() {
                      return new Module.ButtonBlockModel();
                  },
              }
          },
      });

      App.on('before:start', function() {
          App.registerBlockType('button', {
              blockModel: Module.ButtonBlockModel,
              blockView: Module.ButtonBlockView,
          });

          App.registerWidget({
              name: 'button',
              widgetView: Module.ButtonWidgetView,
              priority: 92,
          });
      });
  });

});
