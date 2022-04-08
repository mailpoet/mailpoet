import _ from 'underscore';
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

const BlockModel = BaseBlock.BlockModel.extend({
  stale: ['contents', 'selected'],
  defaults() {
    return this._getDefaults(
      {
        type: 'woocommerceHeading',
        selected: 'completed_order',
      },
      App.getConfig().get('blockDefaults.woocommerceHeading'),
    );
  },
});

const BlockToolsView = BaseBlock.BlockToolsView.extend({
  tools: { move: true },
});

const WidgetView = BaseBlock.WidgetView.extend({
  className:
    BaseBlock.WidgetView.prototype.className +
    ' mailpoet_droppable_layout_block',
  getTemplate() {
    return window.templates.woocommerceHeadingInsertion;
  },
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
  className:
    'mailpoet_container mailpoet_woocommerce_heading_block mailpoet_droppable_block',
  initialize: function initialize() {
    BaseBlock.BlockView.prototype.initialize.apply(this, arguments);
    this.listenTo(App.getChannel(), 'changeWCEmailType', (value) => {
      this.model.set('selected', value);
      this.render();
    });
  },
  modelEvents: _.omit(BaseBlock.BlockView.prototype.modelEvents, 'change'),
  getTemplate() {
    return window.templates.woocommerceHeadingBlock;
  },
  regions: {
    toolsRegion: '.mailpoet_tools',
  },
  onDragSubstituteBy() {
    return WidgetView;
  },
  onRender() {
    this.toolsView = new BlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
  },
  templateContext() {
    const contents = this.model.get('contents').toJSON();
    const selected = this.model.get('selected');
    return {
      viewCid: this.cid,
      model: this.model.toJSON(),
      content: contents[selected],
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
  BlockModel,
  BlockView,
  BlockToolsView,
  WidgetView,
};
