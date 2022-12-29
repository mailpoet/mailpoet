/**
 * wysija_shortcodes/plugin.js
 *
 * TinyMCE plugin for adding dynamic data placeholders to newsletters.
 *
 * This adds a button to the editor toolbar which displays a modal window of
 * available dynamic data placeholder buttons. On click each button inserts
 * its placeholder into editor text.
 */
import { __ } from '@wordpress/i18n';

export function tinyMceAdd(editor) {
  editor.options.register('mailpoet_shortcodes', {
    processor: 'object',
    default: {},
  });

  editor.options.register('mailpoet_shortcodes_window_title', {
    processor: 'string',
    default: '',
  });

  editor.ui.registry.addIcon(
    'mailpoet',
    '<svg viewBox="0 0 152.02 156.4" width="20" height="20"><path d="M37.71,89.1c3.5,0,5.9-.8,7.2-2.3a8,8,0,0,0,2-5.4V35.7l17,45.1a12.68,12.68,0,0,0,3.7,5.4c1.6,1.3,4,2,7.2,2a12.54,12.54,0,0,0,5.9-1.4,8.41,8.41,0,0,0,3.9-5l18.1-50V81a8.53,8.53,0,0,0,2.1,6.1c1.4,1.4,3.7,2.2,6.9,2.2,3.5,0,5.9-.8,7.2-2.3a8,8,0,0,0,2-5.4V8.7a7.48,7.48,0,0,0-3.3-6.6c-2.1-1.4-5-2.1-8.6-2.1a19.3,19.3,0,0,0-9.4,2,11.63,11.63,0,0,0-5.1,6.8L74.91,67.1,54.41,8.4a12.4,12.4,0,0,0-4.5-6.2c-2.1-1.5-5-2.2-8.8-2.2a16.51,16.51,0,0,0-8.9,2.1c-2.3,1.5-3.5,3.9-3.5,7.2V80.8c0,2.8.7,4.8,2,6.2C32.21,88.4,34.41,89.1,37.71,89.1Z"/><path d="M149,116.6l-2.4-1.9a7.4,7.4,0,0,0-9.4.3,19.65,19.65,0,0,1-12.5,4.6h-21.4A37.08,37.08,0,0,0,77,130.5l-1.1,1.2-1.1-1.1a37.25,37.25,0,0,0-26.3-10.9H27a19.59,19.59,0,0,1-12.4-4.6,7.28,7.28,0,0,0-9.4-.3l-2.4,1.9A7.43,7.43,0,0,0,0,122.2a7.14,7.14,0,0,0,2.4,5.7A37.28,37.28,0,0,0,27,137.4h21.6a19.59,19.59,0,0,1,18.9,14.4v.2c.1.7,1.2,4.4,8.5,4.4s8.4-3.7,8.5-4.4v-.2a19.59,19.59,0,0,1,18.9-14.4H125a37.28,37.28,0,0,0,24.6-9.5,7.42,7.42,0,0,0,2.4-5.7A7.86,7.86,0,0,0,149,116.6Z"/></svg>',
  );

  editor.ui.registry.addButton('mailpoet_shortcodes', {
    icon: 'mailpoet',
    onAction: function onActionButton() {
      var shortcodes = [];
      var configShortcodes = editor.options.get('mailpoet_shortcodes');
      var i;

      Object.keys(configShortcodes).forEach(function configShortcodesLoop(
        segment,
      ) {
        var section;
        if (Object.prototype.hasOwnProperty.call(configShortcodes, segment)) {
          section = {
            name: segment,
            title: segment,
            items: [],
          };

          for (i = 0; i < configShortcodes[segment].length; i += 1) {
            section.items.push({
              type: 'button',
              text: configShortcodes[segment][i].text,
              name: configShortcodes[segment][i].shortcode,
            });
          }
          shortcodes.push(section);
        }
      });

      // Open window
      editor.windowManager.open({
        title: editor.options.get('mailpoet_shortcodes_window_title'),
        body: {
          type: 'tabpanel',
          tabs: shortcodes,
        },
        buttons: [
          {
            type: 'cancel',
            text: __('Close', 'mailpoet'),
            primary: true,
          },
        ],
        onAction: function onActionDialog(dialog, button) {
          editor.insertContent(button.name);
          editor.windowManager.close();
        },
      });
    },
  });
}
