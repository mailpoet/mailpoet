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
tinymce.PluginManager.add('mailpoet_shortcodes', function (editor) { // eslint-disable-line func-names
  var appendLabelAndClose = function (shortcode) { // eslint-disable-line func-names
    editor.insertContent(shortcode);
    editor.windowManager.close();
  };
  var generateOnClickFunc = function (shortcode) { // eslint-disable-line func-names
    return function () { // eslint-disable-line func-names
      appendLabelAndClose(shortcode);
    };
  };

  editor.addButton('mailpoet_shortcodes', {
    icon: 'mailpoet_shortcodes',
    onclick: function () { // eslint-disable-line func-names
      var shortcodes = [];
      var configShortcodes = editor.settings.mailpoet_shortcodes;
      var segment;
      var i;

      for (segment in configShortcodes) {
        if (configShortcodes.hasOwnProperty(segment)) {
          shortcodes.push({
            type: 'label',
            text: segment
          });

          for (i = 0; i < configShortcodes[segment].length; i += 1) {
            shortcodes.push({
              type: 'button',
              text: configShortcodes[segment][i].text,
              onClick: generateOnClickFunc(configShortcodes[segment][i].shortcode)
            });
          }
        }
      }

      // Open window
      editor.windowManager.open({
        height: parseInt(editor.getParam('plugin_mailpoet_shortcodes_height', 400)),
        width: parseInt(editor.getParam('plugin_mailpoet_shortcodes_width', 450)),
        autoScroll: true,
        title: editor.settings.mailpoet_shortcodes_window_title,
        body: shortcodes,
        buttons: []
      });
    }
  });
});
