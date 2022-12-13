/**
 * Text Editor Behavior
 *
 * Adds TinyMCE text editing capabilities to a view
 */
import Marionette from 'backbone.marionette';
import tinymce from 'tinymce/tinymce';
import { BehaviorsLookup } from 'newsletter_editor/behaviors/BehaviorsLookup';
import { App } from 'newsletter_editor/App';
import { tinyMceAdd } from 'newsletter_editor/tinymce/mailpoet_shortcodes.js';


// TinyMCE theme and plugins
import 'tinymce/themes/silver';
import 'tinymce/plugins/code';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';

import './tinymce_icons';

var BL = BehaviorsLookup;

BL.TextEditorBehavior = Marionette.Behavior.extend({
  defaults: {
    selector: '.mailpoet_content',
    toolbar1: 'bold italic link unlink forecolor mailpoet_shortcodes',
    toolbar2: '',
    validElements:
      'p[class|style],span[class|style],a[href|class|title|target|style],strong[class|style],em[class|style],strike,br,del',
    invalidElements: 'script',
    blockFormats: 'Paragraph=p',
    plugins: 'link mailpoet_shortcodes',
    configurationFilter: function configurationFilter(originalConfig) {
      return originalConfig;
    },
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

    tinymce.PluginManager.add('mailpoet_shortcodes', tinyMceAdd);

    tinymce.init(
      this.options.configurationFilter({
        target: this.el.querySelector(this.options.selector),
        inline: true,
        contextmenu: false,

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
            this.settings.remove_script_host,
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
            if (App.getDisplayedSettingsId()) {
              App.getChannel().trigger('hideSettings');
            }
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
        },
      }),
    );
  },
});
