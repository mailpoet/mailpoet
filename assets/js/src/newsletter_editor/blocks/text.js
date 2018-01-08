'use strict';

/**
 * Text content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'underscore',
  'mailpoet'
], function (App, BaseBlock, _, MailPoet) { // eslint-disable-line func-names
  var Module = {};
  var base = BaseBlock;

  Module.TextBlockModel = base.BlockModel.extend({
    defaults: function () { // eslint-disable-line func-names
      return this._getDefaults({
        type: 'text',
        text: 'Edit this to insert text'
      }, App.getConfig().get('blockDefaults.text'));
    }
  });

  Module.TextBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_text_block mailpoet_droppable_block',
    getTemplate: function () { return window.templates.textBlock; }, // eslint-disable-line func-names
    modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'), // Prevent rerendering on model change due to text editor redrawing
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      TextEditorBehavior: {
        toolbar1: 'formatselect bold italic forecolor | link unlink',
        toolbar2: 'alignleft aligncenter alignright alignjustify | bullist numlist blockquote | code mailpoet_shortcodes',
        validElements: 'p[class|style],span[class|style],a[href|class|title|target|style],h1[class|style],h2[class|style],h3[class|style],ol[class|style],ul[class|style],li[class|style],strong[class|style],em[class|style],strike,br,blockquote[class|style],table[class|style],tr[class|style],th[class|style],td[class|style]',
        invalidElements: 'script',
        blockFormats: 'Heading 1=h1;Heading 2=h2;Heading 3=h3;Paragraph=p',
        plugins: 'link lists code textcolor colorpicker mailpoet_shortcodes paste',
        configurationFilter: function (originalSettings) { // eslint-disable-line func-names
          return _.extend({}, originalSettings, {
            mailpoet_shortcodes: App.getConfig().get('shortcodes').toJSON(),
            mailpoet_shortcodes_window_title: MailPoet.I18n.t('shortcodesWindowTitle')
          });
        }
      }
    }),
    initialize: function (options) { // eslint-disable-line func-names
      base.BlockView.prototype.initialize.apply(this, arguments);

      this.renderOptions = _.defaults(options.renderOptions || {}, {
        disableTextEditor: false
      });

      this.disableTextEditor = this.renderOptions.disableTextEditor;
    },
    onDragSubstituteBy: function () { return Module.TextWidgetView; }, // eslint-disable-line func-names
    onRender: function () { // eslint-disable-line func-names
      this.toolsView = new Module.TextBlockToolsView({
        model: this.model,
        tools: {
          settings: false
        }
      });
      this.showChildView('toolsRegion', this.toolsView);
    },
    onTextEditorChange: function (newContent) { // eslint-disable-line func-names
      this.model.set('text', newContent);
    },
    onTextEditorFocus: function () { // eslint-disable-line func-names
      this.disableDragging();
      this.disableShowingTools();
    },
    onTextEditorBlur: function () { // eslint-disable-line func-names
      this.enableDragging();
      this.enableShowingTools();
    }
  });

  Module.TextBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function () { return Module.TextBlockSettingsView; } // eslint-disable-line func-names
  });

  Module.TextBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function () { return window.templates.textBlockSettings; } // eslint-disable-line func-names
  });

  Module.TextWidgetView = base.WidgetView.extend({
    getTemplate: function () { return window.templates.textInsertion; }, // eslint-disable-line func-names
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function () { // eslint-disable-line func-names
          return new Module.TextBlockModel();
        }
      }
    }
  });

  App.on('before:start', function (BeforeStartApp) { // eslint-disable-line func-names
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
