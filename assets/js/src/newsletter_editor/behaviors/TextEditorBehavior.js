/**
 * Text Editor Behavior
 *
 * Adds TinyMCE text editing capabilities to a view
 */
define([
  'backbone.marionette',
  'underscore',
  'newsletter_editor/behaviors/BehaviorsLookup'
], function (Marionette, _, BehaviorsLookup) {
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
      configurationFilter: function (originalConfig) { return originalConfig; }
    },
    onDomRefresh: function () {
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
        urlconverter_callback: function (url) {
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

        setup: function (editor) {
          editor.on('change', function () {
            that.view.triggerMethod('text:editor:change', editor.getContent());
          });

          editor.on('click', function (e) {
            editor.focus();
            if (that._isActivationClick) {
              editor.selection.setRng(
                  window.tinymce.dom.RangeUtils.getCaretRangeFromPoint(e.clientX, e.clientY, editor.getDoc())
              );
              that._isActivationClick = false;
            }
          });

          editor.on('focus', function () {
            that.view.triggerMethod('text:editor:focus');
            that._isActivationClick = true;
          });

          editor.on('blur', function () {
            that.view.triggerMethod('text:editor:blur');
          });
        }
      }));
    }
  });
});
