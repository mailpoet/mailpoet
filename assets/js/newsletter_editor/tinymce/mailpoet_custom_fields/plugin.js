/**
 * wysija_custom_fields/plugin.js
 *
 * TinyMCE plugin for adding dynamic data placeholders to newsletters.
 *
 * This adds a button to the editor toolbar which displays a modal window of
 * available dynamic data placeholder buttons. On click each button inserts
 * its placeholder into editor text.
 */

/*jshint unused:false */
/*global tinymce:true */

tinymce.PluginManager.add('mailpoet_custom_fields', function(editor, url) {
    var appendLabelAndClose = function(text) {
            editor.insertContent('[' + text + ']');
            editor.windowManager.close();
        },
        generateOnClickFunc = function(id) {
            return function() {
                appendLabelAndClose(id);
            };
        };

    editor.addButton('mailpoet_custom_fields', {
        icon: 'mailpoet_custom_fields',
        onclick: function() {
            var customFields = [],
                configCustomFields = editor.settings.mailpoet_custom_fields;

            for (var segment in configCustomFields) {
                if (configCustomFields.hasOwnProperty(segment)) {
                    customFields.push({
                        type: 'label',
                        text: segment,
                    });

                    for (var i = 0; i < configCustomFields[segment].length; i += 1) {
                        customFields.push({
                            type: 'button',
                            text: configCustomFields[segment][i].text,
                            onClick: generateOnClickFunc(configCustomFields[segment][i].shortcode)
                        });
                    }
                }
            }

            // Open window
            editor.windowManager.open({
                height: parseInt(editor.getParam("plugin_mailpoet_custom_fields_height", 400)),
                width: parseInt(editor.getParam("plugin_mailpoet_custom_fields_width", 450)),
                autoScroll: true,
                title: editor.settings.mailpoet_custom_fields_window_title,
                body: customFields,
                buttons: [],
            });
        },
    });
});
