/**
 * Spacer content block
 */
define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/base',
    'underscore'
  ], function(App, BaseBlock, _) {

  'use strict';

  var Module = {},
      base = BaseBlock;

  Module.SpacerBlockModel = base.BlockModel.extend({
    defaults: function() {
      return this._getDefaults({
        type: 'spacer',
        styles: {
          block: {
            backgroundColor: 'transparent',
            height: '40px'
          }
        }
      }, App.getConfig().get('blockDefaults.spacer'));
    }
  });

  Module.SpacerBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_spacer_block mailpoet_droppable_block',
    getTemplate: function() { return templates.spacerBlock; },
    behaviors: _.defaults({
      ResizableBehavior: {
        elementSelector: '.mailpoet_spacer',
        resizeHandleSelector: '.mailpoet_resize_handle',
        minLength: 20, // TODO: Move this number to editor configuration
        modelField: 'styles.block.height'
      },
      ShowSettingsBehavior: {
        ignoreFrom: '.mailpoet_resize_handle'
      }
    }, base.BlockView.prototype.behaviors),
    modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'),
    onDragSubstituteBy: function() { return Module.SpacerWidgetView; },
    initialize: function() {
      base.BlockView.prototype.initialize.apply(this, arguments);

      this.listenTo(this.model, 'change:styles.block.backgroundColor', this.render);
      this.listenTo(this.model, 'change:styles.block.height', this.changeHeight);
    },
    onRender: function() {
      this.toolsView = new Module.SpacerBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);
    },
    changeHeight: function() {
      this.$('.mailpoet_spacer').css('height', this.model.get('styles.block.height'));
      this.$('.mailpoet_resize_handle_text').text(this.model.get('styles.block.height'));
    },
    onBeforeDestroy: function() {
      this.stopListening(this.model);
    }
  });

  Module.SpacerBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.SpacerBlockSettingsView; }
  });

  Module.SpacerBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.spacerBlockSettings; },
    events: function() {
      return {
        'change .mailpoet_field_spacer_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
        'click .mailpoet_done_editing': 'close'
      };
    }
  });

  Module.SpacerWidgetView = base.WidgetView.extend({
    getTemplate: function() { return templates.spacerInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.SpacerBlockModel();
        }
      }
    }
  });

  App.on('before:start', function(App, options) {
    App.registerBlockType('spacer', {
      blockModel: Module.SpacerBlockModel,
      blockView: Module.SpacerBlockView
    });

    App.registerWidget({
      name: 'spacer',
      widgetView: Module.SpacerWidgetView,
      priority: 94
    });
  });

  return Module;
});
