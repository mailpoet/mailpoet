/**
 * wysija_shortcodes/plugin.js
 *
 * TinyMCE plugin for adding dynamic data placeholders to newsletters.
 *
 * This adds a button to the editor toolbar which displays a modal window of
 * available dynamic data placeholder buttons. On click each button inserts
 * its placeholder into editor text.
 */

/* jshint unused:false */
/* global tinymce:true */
tinymce.PluginManager.add('mailpoet_shortcodes', function tinyMceAdd(editor) {
  var appendLabelAndClose = function appendLabelAndCLose(shortcode) {
    editor.insertContent(shortcode);
    editor.windowManager.close();
  };
  var generateOnClickFunc = function generateOnClickFunc(shortcode) {
    return function appendAndClose() {
      appendLabelAndClose(shortcode);
    };
  };

  editor.addButton('mailpoet_shortcodes', {
    icon: 'mailpoet_shortcodes',
    onclick: function onClick() {
      var shortcodes = [];
      var configShortcodes = editor.settings.mailpoet_shortcodes;
      var i;

      Object.keys(configShortcodes).forEach(function configShortcodesLoop(segment) {
        if (Object.prototype.hasOwnProperty.call(configShortcodes, segment)) {
          shortcodes.push({
            type: 'label',
            text: segment,
          });

          for (i = 0; i < configShortcodes[segment].length; i += 1) {
            shortcodes.push({
              type: 'button',
              text: configShortcodes[segment][i].text,
              onClick: generateOnClickFunc(configShortcodes[segment][i].shortcode),
            });
          }
        }
      });

      // Open window
      editor.windowManager.open({
        height: parseInt(editor.getParam('plugin_mailpoet_shortcodes_height', 400), 10),
        width: parseInt(editor.getParam('plugin_mailpoet_shortcodes_width', 450), 10),
        autoScroll: true,
        title: editor.settings.mailpoet_shortcodes_window_title,
        body: shortcodes,
        buttons: [],
      });
    },
  });
});
