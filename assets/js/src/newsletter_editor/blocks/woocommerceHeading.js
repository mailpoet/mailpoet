import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

const BlockModel = BaseBlock.BlockModel.extend({
  stale: ['styles.backgroundColor', 'contents', 'selected'],
  defaults() {
    return this._getDefaults({
      type: 'woocommerceHeading',
      selected: 'new_account',
      styles: {
        fontColor: '#000000',
        backgroundColor: '#FFFFFF',
      },
    }, App.getConfig().get('blockDefaults.woocommerceHeading'));
  },
});

const SettingsView = BaseBlock.BlockSettingsView.extend({
  getTemplate: function getTemplate() { return window.templates.woocommerceHeadingBlockSettings; },
  templateContext() {
    return {
      model: this.model.toJSON(),
      styles: this.model.get('styles').toJSON(),
    };
  },
  events: function events() {
    return {
      'change .mailpoet_field_wc_heading_font_color': _.partial(this.changeColorField, 'styles.fontColor'),
      'change .mailpoet_field_wc_heading_background_color': this.backgroundColorChanged,
      'click .mailpoet_done_editing': 'close',
    };
  },
  backgroundColorChanged: function backgroundColorChanged(event) {
    this.changeColorField('styles.backgroundColor', event);
    App.getChannel().trigger('changeWoocommerceBaseColor', jQuery(event.target).val());
  },
  close: function close() {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'woocommerce_settings',
      action: 'set',
      data: {
        woocommerce_email_base_color: this.model.get('styles.backgroundColor'),
      },
    });
    this.destroy();
  },
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
  className: 'mailpoet_container mailpoet_woocommerce_heading_block mailpoet_droppable_block',
  initialize: function initialize() {
    BaseBlock.BlockView.prototype.initialize.apply(this, arguments);
    this.listenTo(this.model, 'change:styles.fontColor', this.render);
    this.listenTo(this.model, 'change:styles.backgroundColor', this.render);
    this.listenTo(App.getChannel(), 'changeWCEmailType', (value) => {
      this.model.set('selected', value);
      this.render();
    });
  },
  modelEvents: _.omit(BaseBlock.BlockView.prototype.modelEvents, 'change'),
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
    const contents = this.model.get('contents').toJSON();
    const selected = this.model.get('selected');
    return {
      viewCid: this.cid,
      model: this.model.toJSON(),
      content: contents[selected],
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
