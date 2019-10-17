import _ from 'underscore';
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

const BlockModel = BaseBlock.BlockModel.extend({
  stale: ['styles'],
  defaults() {
    return this._getDefaults({
      type: 'woocommerceHeading',
      styles: {
        fontColor: '#000000',
        backgroundColor: '#FFFFFF',
      },
    }, App.getConfig().get('blockDefaults.woocommerceHeading'));
  },
});

const SettingsView = BaseBlock.BlockSettingsView.extend({
  getTemplate: function getTemplate() { return window.templates.woocommerceHeadingBlockSettings; },
});

const BlockToolsView = BaseBlock.BlockToolsView.extend({
  tools: {
    move: true,
    settings: true,
  },
  getSettingsView: () => SettingsView,
});

const WidgetView = BaseBlock.WidgetView.extend({
  className: BaseBlock.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  getTemplate() { return window.templates.woocommerceHeadingInsertion; },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop() {
        return new BlockModel({}, { parse: true });
      },
    },
  },
});

const BlockView = BaseBlock.BlockView.extend({
  className: 'mailpoet_block mailpoet_woocommerce_heading_block mailpoet_droppable_block',
  getTemplate() { return window.templates.woocommerceHeadingBlock; },
  behaviors: _.defaults({
    ShowSettingsBehavior: {},
  }, BaseBlock.BlockView.prototype.behaviors),
  regions: {
    toolsRegion: '.mailpoet_tools',
  },
  onDragSubstituteBy() { return WidgetView; },
  onRender() {
    this.toolsView = new BlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
  },
  templateContext() {
    return {
      viewCid: this.cid,
      model: this.model.toJSON(),
      styles: this.model.get('styles').toJSON(),
    };
  },
});

App.on('before:start', (BeforeStartApp) => {
  BeforeStartApp.registerBlockType('woocommerceHeading', {
    blockModel: BlockModel,
    blockView: BlockView,
  });
});

export default {
  BlockModel, BlockView, BlockToolsView, WidgetView,
};
