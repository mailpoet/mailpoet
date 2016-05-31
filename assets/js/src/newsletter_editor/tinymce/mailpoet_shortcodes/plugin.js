/**
 * wysija_shortcodes/plugin.js
 *
 * TinyMCE plugin for adding dynamic data placeholders to newsletters.
 *
 * This adds a button to the editor toolbar which displays a modal window of
 * available dynamic data placeholder buttons. On click each button inserts
 * its placeholder into editor text.
 */

/*jshint unused:false */
/*global tinymce:true */
tinymce.PluginManager.add('mailpoet_shortcodes', function(editor, url) {
  var appendLabelAndClose = function(text) {
      editor.insertContent('[' + text + ']');
      editor.windowManager.close();
    },
    generateOnClickFunc = function(id) {
      return function() {
        appendLabelAndClose(id);
      };
    };

  editor.addButton('mailpoet_shortcodes', {
    icon: 'mailpoet_shortcodes',
    onclick: function() {
      var shortcodes = [],
        configshortcodes = editor.settings.mailpoet_shortcodes;

      for (var segment in configshortcodes) {
        if (configshortcodes.hasOwnProperty(segment)) {
          shortcodes.push({
            type: 'label',
            text: segment,
          });

          for (var i = 0; i < configshortcodes[segment].length; i += 1) {
            shortcodes.push({
              type: 'button',
              text: configshortcodes[segment][i].text,
              onClick: generateOnClickFunc(configshortcodes[segment][i].shortcode)
            });
          }
        }
      }

      // Open window
      editor.windowManager.open({
        height: parseInt(editor.getParam("plugin_mailpoet_shortcodes_height", 400)),
        width: parseInt(editor.getParam("plugin_mailpoet_shortcodes_width", 450)),
        autoScroll: true,
        title: editor.settings.mailpoet_shortcodes_window_title,
        body: shortcodes,
        buttons: [],
      });
    },
  });
});
