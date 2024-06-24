/**
 * Text Editor Behavior
 *
 * Adds TinyMCE text editing capabilities to a view
 */
import Marionette from 'backbone.marionette';
import tinymce, { RawEditorOptions } from 'tinymce/tinymce';
import { BehaviorsLookup } from 'newsletter-editor/behaviors/behaviors-lookup';
import { App } from 'newsletter-editor/app';
import { tinyMceAdd } from 'newsletter-editor/tinymce/mailpoet-shortcodes.js';

// TinyMCE additions
import 'tinymce/themes/silver';
import 'tinymce/plugins/code';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/models/dom';

import './tinymce-icons';

const configurationFilter = (originalConfig: RawEditorOptions) =>
  originalConfig;

BehaviorsLookup.TextEditorBehavior = Marionette.Behavior.extend({
  defaults: {
    selector: '.mailpoet_content',
    toolbar1: 'bold italic link unlink forecolor mailpoet_shortcodes',
    toolbar2: '',
    validElements:
      'p[class|style],span[class|style],a[href|class|title|target|style],strong[class|style],em[class|style],strike,br,del',
    invalidElements: 'script',
    blockFormats: 'Paragraph=p',
    plugins: 'link mailpoet_shortcodes',
    configurationFilter,
  },
  initialize: function initialize() {
    this.listenTo(App.getChannel(), 'dragStart', this.hideEditor);
  },
  hideEditor: function hideEditor() {
    if (this.tinymceEditor) {
      this.tinymceEditor.fire('blur');
    }
  },
  onDomRefresh: async function onDomRefresh() {
    if (this.view.disableTextEditor === true) {
      return;
    }

    tinymce.PluginManager.add('mailpoet_shortcodes', tinyMceAdd);

    await tinymce.init(
      (this.options.configurationFilter as typeof configurationFilter)({
        target: this.el.querySelector(this.options.selector),
        inline: true,
        contextmenu: false,
        license_key: 'gpl',

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
            this.options.get('remove_script_host'),
          );
        },

        plugins: this.options.plugins,

        setup: (editor) => {
          // Store the editor instance
          this.tinymceEditor = editor;
          editor.on('change', () => {
            this.view.triggerMethod('text:editor:change', editor.getContent());
          });

          editor.on('click', (e) => {
            if (App.getDisplayedSettingsId()) {
              App.getChannel().trigger('hideSettings');
            }
            // if caret not in editor, place it there (triggers focus on editor)
            if (document.activeElement !== editor.targetElm) {
              editor.selection.placeCaretAt(e.clientX, e.clientY);
            }
          });

          editor.on('focus', () => {
            this.view.triggerMethod('text:editor:focus');
          });

          editor.on('blur', () => {
            this.view.triggerMethod('text:editor:blur');
          });
        },
      }),
    );
  },
});
