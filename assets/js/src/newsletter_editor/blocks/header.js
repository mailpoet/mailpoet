/**
 * Header content block
 */
define([
    'newsletter_editor/App',
    'newsletter_editor/blocks/base',
    'underscore'
  ], function(App, BaseBlock, _) {

  "use strict";

  var Module = {},
      base = BaseBlock;

  Module.HeaderBlockModel = base.BlockModel.extend({
    defaults: function() {
      return this._getDefaults({
        type: 'header',
        text: 'Display problems? <a href="[link:newsletter_view_in_browser_url]">View it in your browser</a>',
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
  });

  Module.HeaderBlockView = base.BlockView.extend({
    className: "mailpoet_block mailpoet_header_block mailpoet_droppable_block",
    getTemplate: function() { return templates.headerBlock; },
    modelEvents: _.extend({
      'change:styles.block.backgroundColor change:styles.text.fontColor change:styles.text.fontFamily change:styles.text.fontSize change:styles.text.textAlign change:styles.link.fontColor change:styles.link.textDecoration': 'render',
    }, _.omit(base.BlockView.prototype.modelEvents, 'change')),
    onDragSubstituteBy: function() { return Module.HeaderWidgetView; },
    onRender: function() {
      this.toolsView = new Module.HeaderBlockToolsView({ model: this.model });
      this.toolsRegion.show(this.toolsView);
    },
    onDomRefresh: function() {
      this.attachTextEditor();
    },
    attachTextEditor: function() {
      var that = this;
      this.$('.mailpoet_content').tinymce({
        inline: true,

        menubar: false,
        toolbar: "bold italic link unlink forecolor mailpoet_custom_fields",

        valid_elements: "p[class|style],span[class|style],a[href|class|title|target|style],strong[class|style],em[class|style],strike,br",
        invalid_elements: "script",
        block_formats: 'Paragraph=p',

        plugins: "link textcolor colorpicker mailpoet_custom_fields",

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
        mailpoet_custom_fields_window_title: MailPoet.I18n.t('customFieldsWindowTitle'),
      });
    },
  });

  Module.HeaderBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.HeaderBlockSettingsView; },
  });

  Module.HeaderBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.headerBlockSettings; },
    events: function() {
      return {
        "change .mailpoet_field_header_text_color": _.partial(this.changeColorField, "styles.text.fontColor"),
        "change .mailpoet_field_header_text_font_family": _.partial(this.changeField, "styles.text.fontFamily"),
        "change .mailpoet_field_header_text_size": _.partial(this.changeField, "styles.text.fontSize"),
        "change #mailpoet_field_header_link_color": _.partial(this.changeColorField, "styles.link.fontColor"),
        "change #mailpoet_field_header_link_underline": function(event) {
          this.model.set('styles.link.textDecoration', (event.target.checked) ? event.target.value : 'none');
        },
        "change .mailpoet_field_header_background_color": _.partial(this.changeColorField, "styles.block.backgroundColor"),
        "change .mailpoet_field_header_alignment": _.partial(this.changeField, "styles.text.textAlign"),
        "click .mailpoet_done_editing": "close",
      };
    },
    behaviors: {
      ColorPickerBehavior: {},
    },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
        availableStyles: App.getAvailableStyles().toJSON(),
      };
    },
  });

  Module.HeaderWidgetView = base.WidgetView.extend({
    getTemplate: function() { return templates.headerInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.HeaderBlockModel();
        },
      }
    },
  });

  App.on('before:start', function() {
    App.registerBlockType('header', {
      blockModel: Module.HeaderBlockModel,
      blockView: Module.HeaderBlockView,
    });

    App.registerWidget({
      name: 'header',
      widgetView: Module.HeaderWidgetView,
      priority: 98,
    });
  });

  return Module;
});
