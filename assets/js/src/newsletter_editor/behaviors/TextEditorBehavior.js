/**
 * Text Editor Behavior
 *
 * Adds TinyMCE text editing capabilities to a view
 */
define([
  'backbone.marionette',
  'underscore',
  'newsletter_editor/behaviors/BehaviorsLookup',
  'newsletter_editor/App'
], function textEditorBehavior(Marionette, _, BehaviorsLookup, App) {
  var BL = BehaviorsLookup;

  BL.TextEditorBehavior = Marionette.Behavior.extend({
    defaults: {
      selector: '.mailpoet_content',
      toolbar1: 'bold italic link unlink forecolor mailpoet_shortcodes',
      toolbar2: '',
      validElements: 'p[class|style],span[class|style],a[href|class|title|target|style],strong[class|style],em[class|style],strike,br',
      invalidElements: 'script',
      blockFormats: 'Paragraph=p',
      plugins: 'link textcolor colorpicker mailpoet_shortcodes',
      configurationFilter: function configurationFilter(originalConfig) { return originalConfig; }
    },
    initialize: function initialize() {
      this.listenTo(App.getChannel(), 'dragStart', this.hideEditor);
    },
    hideEditor: function hideEditor() {
      if (this.tinymceEditor) {
        this.tinymceEditor.fire('blur');
      }
    },
    onDomRefresh: function onDomRefresh() {
      var that = this;
      if (this.view.disableTextEditor === true) {
        return;
      }

      this.$(this.options.selector).tinymce(this.options.configurationFilter({
        inline: true,

        menubar: false,
        toolbar1: this.options.toolbar1,
        toolbar2: this.options.toolbar2,

        browser_spellcheck: true,

        valid_elements: this.options.validElements,
        invalid_elements: this.options.invalidElements,
        block_formats: this.options.blockFormats,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        urlconverter_callback: function urlconverterCallback(url) {
          if (url.match(/\[.+\]/g)) {
            // Do not convert URLs with shortcodes
            return url;
          }

          return this.documentBaseURI.toAbsolute(
            url,
            this.settings.remove_script_host
          );
        },

        plugins: this.options.plugins,

        setup: function setup(editor) {
          // Store the editor instance
          that.tinymceEditor = editor;
          editor.on('change', function onChange() {
            that.view.triggerMethod('text:editor:change', editor.getContent());
          });

          editor.on('click', function onClick(e) {
            // if caret not in editor, place it there (triggers focus on editor)
            if (document.activeElement !== editor.targetElm) {
              editor.selection.placeCaretAt(e.clientX, e.clientY);
            }
          });

          editor.on('focus', function onFocus() {
            that.view.triggerMethod('text:editor:focus');
          });

          editor.on('blur', function onBlur() {
            that.view.triggerMethod('text:editor:blur');
          });
        }
      }));
    }
  });
});
