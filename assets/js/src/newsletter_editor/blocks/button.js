/**
 * Button content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'mailpoet',
  'underscore',
  'jquery'
], function(App, BaseBlock, MailPoet, _, jQuery) {

  'use strict';

  var Module = {},
    base = BaseBlock;

  Module.ButtonBlockModel = base.BlockModel.extend({
    defaults: function() {
      return this._getDefaults({
        type: 'button',
        text: 'Button',
        url: '',
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
            fontWeight: 'normal', // 'normal'|'bold'
            textAlign: 'center'
          }
        }
      }, App.getConfig().get('blockDefaults.button'));
    }
  });

  Module.ButtonBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_button_block mailpoet_droppable_block',
    getTemplate: function() { return templates.buttonBlock; },
    onDragSubstituteBy: function() { return Module.ButtonWidgetView; },
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      ShowSettingsBehavior: {}
    }),
    initialize: function() {
      base.BlockView.prototype.initialize.apply(this, arguments);

      // Listen for attempts to change all dividers in one go
      this._replaceButtonStylesHandler = function(data) { this.model.set(data); }.bind(this);
      App.getChannel().on('replaceAllButtonStyles', this._replaceButtonStylesHandler);
    },
    onRender: function() {
      this.toolsView = new Module.ButtonBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);
    }
  });

  Module.ButtonBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.ButtonBlockSettingsView; }
  });

  Module.ButtonBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.buttonBlockSettings; },
    events: function() {
      return {
        'input .mailpoet_field_button_text': _.partial(this.changeField, 'text'),
        'input .mailpoet_field_button_url': _.partial(this.changeField, 'url'),
        'change .mailpoet_field_button_alignment': _.partial(this.changeField, 'styles.block.textAlign'),
        'change .mailpoet_field_button_font_color': _.partial(this.changeColorField, 'styles.block.fontColor'),
        'change .mailpoet_field_button_font_family': _.partial(this.changeField, 'styles.block.fontFamily'),
        'change .mailpoet_field_button_font_size': _.partial(this.changeField, 'styles.block.fontSize'),
        'change .mailpoet_field_button_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
        'change .mailpoet_field_button_border_color': _.partial(this.changeColorField, 'styles.block.borderColor'),
        'change .mailpoet_field_button_font_weight': 'changeFontWeight',

        'input .mailpoet_field_button_border_width': _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width_input', _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this)),
        'change .mailpoet_field_button_border_width': _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width_input', _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this)),
        'input .mailpoet_field_button_border_width_input': _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_width', _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this)),

        'input .mailpoet_field_button_border_radius': _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius_input', _.partial(this.changePixelField, 'styles.block.borderRadius').bind(this)),
        'change .mailpoet_field_button_border_radius': _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius_input', _.partial(this.changePixelField, 'styles.block.borderRadius').bind(this)),
        'input .mailpoet_field_button_border_radius_input': _.partial(this.updateValueAndCall, '.mailpoet_field_button_border_radius', _.partial(this.changePixelField, 'styles.block.borderRadius').bind(this)),

        'input .mailpoet_field_button_width': _.partial(this.updateValueAndCall, '.mailpoet_field_button_width_input', _.partial(this.changePixelField, 'styles.block.width').bind(this)),
        'change .mailpoet_field_button_width': _.partial(this.updateValueAndCall, '.mailpoet_field_button_width_input', _.partial(this.changePixelField, 'styles.block.width').bind(this)),
        'input .mailpoet_field_button_width_input': _.partial(this.updateValueAndCall, '.mailpoet_field_button_width', _.partial(this.changePixelField, 'styles.block.width').bind(this)),

        'input .mailpoet_field_button_line_height': _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height_input', _.partial(this.changePixelField, 'styles.block.lineHeight').bind(this)),
        'change .mailpoet_field_button_line_height': _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height_input', _.partial(this.changePixelField, 'styles.block.lineHeight').bind(this)),
        'input .mailpoet_field_button_line_height_input': _.partial(this.updateValueAndCall, '.mailpoet_field_button_line_height', _.partial(this.changePixelField, 'styles.block.lineHeight').bind(this)),

        'click .mailpoet_field_button_replace_all_styles': 'applyToAll',
        'click .mailpoet_done_editing': 'close'
      };
    },
    templateContext: function() {
      return _.extend({}, base.BlockView.prototype.templateContext.apply(this, arguments), {
        availableStyles: App.getAvailableStyles().toJSON(),
        renderOptions: this.renderOptions
      });
    },
    applyToAll: function() {
      App.getChannel().trigger('replaceAllButtonStyles', _.pick(this.model.toJSON(), 'styles', 'type'));
    },
    updateValueAndCall: function(fieldToUpdate, callable, event) {
      this.$(fieldToUpdate).val(jQuery(event.target).val());
      callable(event);
    },
    changeFontWeight: function(event) {
      var checked = !!jQuery(event.target).prop('checked');
      this.model.set(
        'styles.block.fontWeight',
        (checked) ? jQuery(event.target).val() : 'normal'
      );
    }
  });

  Module.ButtonWidgetView = base.WidgetView.extend({
    getTemplate: function() { return templates.buttonInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.ButtonBlockModel();
        }
      }
    }
  });

  App.on('before:start', function(App, options) {
    App.registerBlockType('button', {
      blockModel: Module.ButtonBlockModel,
      blockView: Module.ButtonBlockView
    });

    App.registerWidget({
      name: 'button',
      widgetView: Module.ButtonWidgetView,
      priority: 92
    });
  });

  return Module;
});
