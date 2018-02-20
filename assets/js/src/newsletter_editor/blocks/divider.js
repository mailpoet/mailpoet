'use strict';

/**
 * Divider content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'underscore',
  'jquery'
], function dividerBlock(App, BaseBlock, _, jQuery) {
  var Module = {};
  var base = BaseBlock;

  Module.DividerBlockModel = base.BlockModel.extend({
    defaults: function defaults() {
      return this._getDefaults({
        type: 'divider',
        styles: {
          block: {
            backgroundColor: 'transparent',
            padding: '12px',
            borderStyle: 'solid',
            borderWidth: '1px',
            borderColor: '#000000'
          }
        }
      }, App.getConfig().get('blockDefaults.divider'));
    }
  });

  Module.DividerBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_divider_block mailpoet_droppable_block',
    getTemplate: function getTemplate() { return window.templates.dividerBlock; },
    modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'),
    behaviors: _.defaults({
      ResizableBehavior: {
        elementSelector: '.mailpoet_content',
        resizeHandleSelector: '.mailpoet_resize_handle',
        transformationFunction: function transformationFunction(y) { return y / 2; },
        minLength: 0, // TODO: Move this number to editor configuration
        modelField: 'styles.block.padding'
      },
      ShowSettingsBehavior: {
        ignoreFrom: '.mailpoet_resize_handle'
      }
    }, base.BlockView.prototype.behaviors),
    onDragSubstituteBy: function onDragSubstituteBy() { return Module.DividerWidgetView; },
    initialize: function initialize() {
      var that = this;
      base.BlockView.prototype.initialize.apply(this, arguments);

      // Listen for attempts to change all dividers in one go
      this._replaceDividerHandler = function replaceDividerHandler(data) {
        that.model.set(data); that.model.trigger('applyToAll');
      };
      App.getChannel().on('replaceAllDividers', this._replaceDividerHandler);

      this.listenTo(this.model, 'change:src change:styles.block.backgroundColor change:styles.block.borderStyle change:styles.block.borderWidth change:styles.block.borderColor applyToAll', this.render);
      this.listenTo(this.model, 'change:styles.block.padding', this.changePadding);
    },
    templateContext: function templateContext() {
      return _.extend({
        totalHeight: (parseInt(this.model.get('styles.block.padding'), 10) * 2) + parseInt(this.model.get('styles.block.borderWidth')) + 'px'
      }, base.BlockView.prototype.templateContext.apply(this));
    },
    onRender: function onRender() {
      this.toolsView = new Module.DividerBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);
    },
    onBeforeDestroy: function onBeforeDestroy() {
      App.getChannel().off('replaceAllDividers', this._replaceDividerHandler);
      this.stopListening(this.model);
    },
    changePadding: function changePadding() {
      this.$('.mailpoet_content').css('padding-top', this.model.get('styles.block.padding'));
      this.$('.mailpoet_content').css('padding-bottom', this.model.get('styles.block.padding'));
      this.$('.mailpoet_resize_handle_text').text((parseInt(this.model.get('styles.block.padding'), 10) * 2) + parseInt(this.model.get('styles.block.borderWidth')) + 'px');
    }
  });

  Module.DividerBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function getSettingsView() { return Module.DividerBlockSettingsView; }
  });

  Module.DividerBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function getTemplate() { return window.templates.dividerBlockSettings; },
    events: function events() {
      return {
        'click .mailpoet_field_divider_style': 'changeStyle',

        'input .mailpoet_field_divider_border_width': _.partial(this.updateValueAndCall, '.mailpoet_field_divider_border_width_input', _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this)),
        'change .mailpoet_field_divider_border_width': _.partial(this.updateValueAndCall, '.mailpoet_field_divider_border_width_input', _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this)),
        'input .mailpoet_field_divider_border_width_input': _.partial(this.updateValueAndCall, '.mailpoet_field_divider_border_width', _.partial(this.changePixelField, 'styles.block.borderWidth').bind(this)),

        'change .mailpoet_field_divider_border_color': _.partial(this.changeColorField, 'styles.block.borderColor'),
        'change .mailpoet_field_divider_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
        'click .mailpoet_button_divider_apply_to_all': 'applyToAll',
        'click .mailpoet_done_editing': 'close'
      };
    },
    modelEvents: function modelEvents() {
      return {
        'change:styles.block.borderColor': 'repaintDividerStyleOptions'
      };
    },
    templateContext: function templateContext() {
      return _.extend({}, base.BlockView.prototype.templateContext.apply(this, arguments), {
        availableStyles: App.getAvailableStyles().toJSON(),
        renderOptions: this.renderOptions
      });
    },
    changeStyle: function changeStyle(event) {
      var style = jQuery(event.currentTarget).data('style');
      this.model.set('styles.block.borderStyle', style);
      this.$('.mailpoet_field_divider_style').removeClass('mailpoet_active_divider_style');
      this.$('.mailpoet_field_divider_style[data-style="' + style + '"]').addClass('mailpoet_active_divider_style');
    },
    repaintDividerStyleOptions: function repaintDividerStyleOptions() {
      this.$('.mailpoet_field_divider_style > div').css('border-top-color', this.model.get('styles.block.borderColor'));
    },
    applyToAll: function applyToAll() {
      App.getChannel().trigger('replaceAllDividers', this.model.toJSON());
    },
    updateValueAndCall: function updateValueAndCall(fieldToUpdate, callable, event) {
      this.$(fieldToUpdate).val(jQuery(event.target).val());
      callable(event);
    }
  });

  Module.DividerWidgetView = base.WidgetView.extend({
    getTemplate: function getTemplate() { return window.templates.dividerInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function drop() {
          return new Module.DividerBlockModel();
        }
      }
    }
  });
  App.on('before:start', function onBeforeStart(BeforeStartApp) {
    BeforeStartApp.registerBlockType('divider', {
      blockModel: Module.DividerBlockModel,
      blockView: Module.DividerBlockView
    });

    BeforeStartApp.registerWidget({
      name: 'divider',
      widgetView: Module.DividerWidgetView,
      priority: 93
    });
  });

  return Module;
});
