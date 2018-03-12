'use strict';

/**
 * Text content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'underscore',
  'mailpoet'
], function textBlock(App, BaseBlock, _, MailPoet) {
  var Module = {};
  var base = BaseBlock;

  Module.TextBlockModel = base.BlockModel.extend({
    defaults: function defaults() {
      return this._getDefaults({
        type: 'text',
        text: 'Edit this to insert text'
      }, App.getConfig().get('blockDefaults.text'));
    },
    _updateDefaults: function updateDefaults() {}
  });

  Module.TextBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_text_block mailpoet_droppable_block',
    getTemplate: function getTemplate() { return window.templates.textBlock; },
    modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'), // Prevent rerendering on model change due to text editor redrawing
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      TextEditorBehavior: {
        toolbar1: 'formatselect bold italic forecolor | link unlink',
        toolbar2: 'alignleft aligncenter alignright alignjustify | bullist numlist blockquote | code mailpoet_shortcodes',
        validElements: 'p[class|style],span[class|style],a[href|class|title|target|style],h1[class|style],h2[class|style],h3[class|style],ol[class|style],ul[class|style],li[class|style],strong[class|style],em[class|style],strike,br,blockquote[class|style],table[class|style],tr[class|style],th[class|style],td[class|style]',
        invalidElements: 'script',
        blockFormats: 'Heading 1=h1;Heading 2=h2;Heading 3=h3;Paragraph=p',
        plugins: 'link lists code textcolor colorpicker mailpoet_shortcodes paste',
        configurationFilter: function configurationFilter(originalSettings) {
          return _.extend({}, originalSettings, {
            mailpoet_shortcodes: App.getConfig().get('shortcodes').toJSON(),
            mailpoet_shortcodes_window_title: MailPoet.I18n.t('shortcodesWindowTitle')
          });
        }
      }
    }),
    initialize: function initialize(options) {
      base.BlockView.prototype.initialize.apply(this, arguments);

      this.renderOptions = _.defaults(options.renderOptions || {}, {
        disableTextEditor: false
      });

      this.disableTextEditor = this.renderOptions.disableTextEditor;
    },
    onDragSubstituteBy: function onDragSubstituteBy() { return Module.TextWidgetView; },
    onRender: function onRender() {
      this.toolsView = new Module.TextBlockToolsView({
        model: this.model,
        tools: {
          settings: false
        }
      });
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
    }
  });

  Module.TextBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function getSettingsView() { return Module.TextBlockSettingsView; }
  });

  Module.TextBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function getTemplate() { return window.templates.textBlockSettings; }
  });

  Module.TextWidgetView = base.WidgetView.extend({
    getTemplate: function getTemplate() { return window.templates.textInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function drop() {
          return new Module.TextBlockModel();
        }
      }
    }
  });

  App.on('before:start', function beforeAppStart(BeforeStartApp) {
    BeforeStartApp.registerBlockType('text', {
      blockModel: Module.TextBlockModel,
      blockView: Module.TextBlockView
    });

    BeforeStartApp.registerWidget({
      name: 'text',
      widgetView: Module.TextWidgetView,
      priority: 90
    });
  });


  return Module;
});
