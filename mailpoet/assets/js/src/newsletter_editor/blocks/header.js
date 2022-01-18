/**
 * Header content block
 */
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';
import _ from 'underscore';
import MailPoet from 'mailpoet';

var Module = {};
var base = BaseBlock;

Module.HeaderBlockModel = base.BlockModel.extend({
  defaults: function defaults() {
    return this._getDefaults({
      type: 'header',
      text: '<a href="[link:newsletter_view_in_browser_url]">View this in your browser</a>',
      styles: {
        block: {
          backgroundColor: 'transparent',
        },
        text: {
          fontColor: '#000000',
          fontFamily: 'Arial',
          fontSize: '12px',
          textAlign: 'center',
        },
        link: {
          fontColor: '#0000ff',
          textDecoration: 'underline',
        },
      },
    }, App.getConfig().get('blockDefaults.header'));
  },
  _updateDefaults: function updateDefaults() {
    App.getConfig().set('blockDefaults.header', _.omit(this.toJSON(), 'text'));
  },
});

Module.HeaderBlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_header_block mailpoet_droppable_block',
  getTemplate: function getTemplate() { return window.templates.headerBlock; },
  modelEvents: _.extend({
    'change:styles.block.backgroundColor change:styles.text.fontColor change:styles.text.fontFamily change:styles.text.fontSize change:styles.text.textAlign change:styles.link.fontColor change:styles.link.textDecoration': 'render',
  }, _.omit(base.BlockView.prototype.modelEvents, 'change')),
  behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
    TextEditorBehavior: {
      configurationFilter: function configurationFilter(originalSettings) {
        return _.extend({}, originalSettings, {
          mailpoet_shortcodes: App.getConfig().get('shortcodes').toJSON(),
          mailpoet_shortcodes_window_title: MailPoet.I18n.t('shortcodesWindowTitle'),
        });
      },
    },
  }),
  onDragSubstituteBy: function onDragSubstituteBy() { return Module.HeaderWidgetView; },
  onRender: function onRender() {
    this.toolsView = new Module.HeaderBlockToolsView({ model: this.model });
    this.showChildView('toolsRegion', this.toolsView);
  },
  onTextEditorChange: function onTextEditorChange(newContent) {
    this.model.set('text', newContent);
  },
  onTextEditorFocus: function onTextEditorFocus() {
    this.disableDragging();
    this.disableShowingTools();
  },
  onTextEditorBlur: function onTextEditorBlur() {
    this.enableDragging();
    this.enableShowingTools();
  },
});

Module.HeaderBlockToolsView = base.BlockToolsView.extend({
  getSettingsView: function getSettingsView() { return Module.HeaderBlockSettingsView; },
});

Module.HeaderBlockSettingsView = base.BlockSettingsView.extend({
  getTemplate: function getTemplate() { return window.templates.headerBlockSettings; },
  events: function events() {
    return {
      'change .mailpoet_field_header_text_color': _.partial(this.changeColorField, 'styles.text.fontColor'),
      'change .mailpoet_field_header_text_font_family': _.partial(this.changeField, 'styles.text.fontFamily'),
      'change .mailpoet_field_header_text_size': _.partial(this.changeField, 'styles.text.fontSize'),
      'change #mailpoet_field_header_link_color': _.partial(this.changeColorField, 'styles.link.fontColor'),
      'change #mailpoet_field_header_link_underline': function linkUnderline(event) {
        this.model.set('styles.link.textDecoration', (event.target.checked) ? event.target.value : 'none');
      },
      'change .mailpoet_field_header_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
      'change .mailpoet_field_header_alignment': _.partial(this.changeField, 'styles.text.textAlign'),
      'click .mailpoet_done_editing': 'close',
    };
  },
  templateContext: function templateContext() {
    return _.extend({}, base.BlockView.prototype.templateContext.apply(this, arguments), {
      availableStyles: App.getAvailableStyles().toJSON(),
    });
  },
});

Module.HeaderWidgetView = base.WidgetView.extend({
  id: 'automation_editor_block_header',
  getTemplate: function getTemplate() { return window.templates.headerInsertion; },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.HeaderBlockModel();
      },
    },
  },
});

App.on('before:start', function beforeAppStart(BeforeStartApp) {
  BeforeStartApp.registerBlockType('header', {
    blockModel: Module.HeaderBlockModel,
    blockView: Module.HeaderBlockView,
  });

  BeforeStartApp.registerWidget({
    name: 'header',
    widgetView: Module.HeaderWidgetView,
    priority: 100,
  });
});

export default Module;
