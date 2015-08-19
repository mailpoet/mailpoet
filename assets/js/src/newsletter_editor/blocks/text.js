/**
 * Text content block
 */
define('newsletter_editor/blocks/text', [
    'newsletter_editor/App',
    'backbone',
    'backbone.supermodel',
    'backbone.marionette',
    'mailpoet',
    'jquery',
    //'tinymce',
    //'jquery.tinymce',
  ], function(EditorApplication, Backbone, SuperModel, Marionette, MailPoet, jQuery, TinyMCE) {

  EditorApplication.module("blocks.text", function(Module, App, Backbone, Marionette, $, _) {
      "use strict";

      var base = App.module('blocks.base');

      Module.TextBlockModel = base.BlockModel.extend({
          defaults: function() {
              return this._getDefaults({
                  type: 'text',
                  text: 'Edit this to insert text',
              }, EditorApplication.getConfig().get('blockDefaults.text'));
          },
      });

      Module.TextBlockView = base.BlockView.extend({
          className: "mailpoet_block mailpoet_text_block mailpoet_droppable_block",
          getTemplate: function() { return templates.textBlock; },
          modelEvents: _.omit(base.BlockView.prototype.modelEvents, 'change'), // Prevent rerendering on model change due to text editor redrawing
          initialize: function(options) {
              this.renderOptions = _.defaults(options.renderOptions || {}, {
                  disableTextEditor: false,
              });
          },
          onDragSubstituteBy: function() { return Module.TextWidgetView; },
          onRender: function() {
              this.toolsView = new Module.TextBlockToolsView({
                  model: this.model,
                  tools: {
                      settings: false,
                  },
              });
              this.toolsRegion.show(this.toolsView);
          },
          onDomRefresh: function() {
              this.attachTextEditor();
          },
          attachTextEditor: function() {
              var that = this;
              if (!this.renderOptions.disableTextEditor) {
                  this.$('.mailpoet_content').tinymce({
                      inline: true,

                      menubar: false,
                      toolbar1: "styleselect bold italic forecolor | link unlink",
                      toolbar2: "alignleft aligncenter alignright alignjustify | bullist numlist blockquote | code mailpoet_custom_fields",

                      //forced_root_block: 'p',
                      valid_elements: "p[class|style],span[class|style],a[href|class|title|target|style],h1[class|style],h2[class|style],h3[class|style],ol[class|style],ul[class|style],li[class|style],strong[class|style],em[class|style],strike,br,blockquote[class|style],table[class|style],tr[class|style],th[class|style],td[class|style]",
                      invalid_elements: "script",
                      style_formats: [
                          {title: 'Heading 1', block: 'h1'},
                          {title: 'Heading 2', block: 'h2'},
                          {title: 'Heading 3', block: 'h3'},

                          {title: 'Paragraph', block: 'p'},
                      ],

                      plugins: "wplink code textcolor mailpoet_custom_fields",

                      setup: function(editor) {
                          editor.on('change', function(e) {
                              that.model.set('text', editor.getContent());
                          });

                          editor.on('focus', function(e) {
                              that.disableShowingTools();
                          });

                          editor.on('blur', function(e) {
                              that.enableShowingTools();
                          });
                      },

                      mailpoet_custom_fields: App.getConfig().get('customFields').toJSON(),
                      mailpoet_custom_fields_window_title: App.getConfig().get('translations.customFieldsWindowTitle'),
                  });
              }
          },
      });

      Module.TextBlockToolsView = base.BlockToolsView.extend({
          getSettingsView: function() { return Module.TextBlockSettingsView; },
      });

      Module.TextBlockSettingsView = base.BlockSettingsView.extend({
          getTemplate: function() { return templates.textBlockSettings; },
      });

      Module.TextWidgetView = base.WidgetView.extend({
          getTemplate: function() { return templates.textInsertion; },
          behaviors: {
              DraggableBehavior: {
                  cloneOriginal: true,
                  drop: function() {
                      return new Module.TextBlockModel();
                  },
              }
          },
      });

      App.on('before:start', function() {
          App.registerBlockType('text', {
              blockModel: Module.TextBlockModel,
              blockView: Module.TextBlockView,
          });

          App.registerWidget({
              name: 'text',
              widgetView: Module.TextWidgetView,
              priority: 90,
          });
      });
  });

});
