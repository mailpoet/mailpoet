/**
 * Spacer content block
 */
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';
import _ from 'underscore';

var Module = {};
var base = BaseBlock;

Module.SpacerBlockModel = base.BlockModel.extend({
  defaults: function defaults() {
    return this._getDefaults({
      type: 'spacer',
      styles: {
        block: {
          backgroundColor: 'transparent',
          height: '40px',
        },
      },
    }, App.getConfig().get('blockDefaults.spacer'));
  },
});

Module.SpacerBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_spacer_block mailpoet_droppable_block',
  getTemplate: function getTemplate() { return window.templates.spacerBlock; },
  behaviors: _.defaults({
    ResizableBehavior: {
      elementSelector: '.mailpoet_spacer',
      resizeHandleSelector: '.mailpoet_resize_handle',
      minLength: 20, // TODO: Move this number to editor configuration
      modelField: 'styles.block.height',
    },
    ShowSettingsBehavior: {
      ignoreFrom: '.mailpoet_resize_handle',
    },
  }, base.BlockView.prototype.behaviors),
  modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'),
  onDragSubstituteBy: function onDragSubstituteBy() { return Module.SpacerWidgetView; },
  initialize: function initialize() {
    base.BlockView.prototype.initialize.apply(this, arguments);

    this.listenTo(this.model, 'change:styles.block.backgroundColor', this.render);
    this.listenTo(this.model, 'change:styles.block.height', this.changeHeight);
  },
  onRender: function onRender() {
    this.toolsView = new Module.SpacerBlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
  },
  changeHeight: function changeHeight() {
    this.$('.mailpoet_spacer').css('height', this.model.get('styles.block.height'));
    this.$('.mailpoet_resize_handle_text').text(this.model.get('styles.block.height'));
  },
  onBeforeDestroy: function onBeforeDestroy() {
    this.stopListening(this.model);
  },
});

Module.SpacerBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function getSettingsView() { return Module.SpacerBlockSettingsView; },
});

Module.SpacerBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() { return window.templates.spacerBlockSettings; },
  events: function events() {
    return {
      'change .mailpoet_field_spacer_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
      'click .mailpoet_done_editing': 'close',
    };
  },
});

Module.SpacerWidgetView = base.WidgetView.extend({
  id: 'automation_editor_block_spacer',
  getTemplate: function getTemplate() { return window.templates.spacerInsertion; },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.SpacerBlockModel();
      },
    },
  },
});

App.on('before:start', function beforeAppStart(BeforeStartApp) {
  BeforeStartApp.registerBlockType('spacer', {
    blockModel: Module.SpacerBlockModel,
    blockView: Module.SpacerBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'spacer',
    widgetView: Module.SpacerWidgetView,
    priority: 94,
  });
});

export default Module;
