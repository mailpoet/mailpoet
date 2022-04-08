import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

const BlockModel = BaseBlock.BlockModel.extend({
  stale: ['selected'],
  defaults() {
    return this._getDefaults(
      {
        type: 'woocommerceContent',
        selected: 'completed_order',
      },
      App.getConfig().get('blockDefaults.woocommerceContent'),
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
    return window.templates.woocommerceContentInsertion;
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
    'mailpoet_block mailpoet_woocommerce_content_block mailpoet_droppable_block',
  initialize: function initialize() {
    BaseBlock.BlockView.prototype.initialize.apply(this, arguments);
    this.listenTo(App.getChannel(), 'changeWCEmailType', (value) => {
      this.model.set('selected', value);
      this.render();
    });
  },
  getTemplate() {
    if (this.model.get('selected') === 'new_account') {
      return window.templates.woocommerceNewAccount;
    }
    if (this.model.get('selected') === 'processing_order') {
      return window.templates.woocommerceProcessingOrder;
    }
    if (this.model.get('selected') === 'completed_order') {
      return window.templates.woocommerceCompletedOrder;
    }
    return window.templates.woocommerceCustomerNote;
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
    return {
      viewCid: this.cid,
      model: this.model.toJSON(),
      selected: this.model.get('selected'),
      siteName: window.mailpoet_site_name,
      siteAddress: window.mailpoet_site_address,
    };
  },
});

App.on('before:start', (BeforeStartApp) => {
  BeforeStartApp.registerBlockType('woocommerceContent', {
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
