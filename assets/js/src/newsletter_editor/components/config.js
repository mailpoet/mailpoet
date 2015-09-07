define([
    'newsletter_editor/App',
    'backbone',
  ], function(EditorApplication, Backbone) {

  EditorApplication.module("components.config", function(Module, App, Backbone, Marionette, $, _) {
    "use strict";

    Module.ConfigModel = Backbone.SuperModel.extend({
      defaults: {
        availableStyles: {},
        socialIcons: {},
        blockDefaults: {},
        translations: {},
        sidepanelWidth: '331px',
        validation: {},
        urls: {},
      },
    });

    // Global and available styles for access in blocks and their settings
    Module._config = {};
    Module.getConfig = function() { return Module._config; };
    Module.setConfig = function(options) {
      Module._config = new Module.ConfigModel(options, { parse: true });
      return Module._config;
    };

    App.on('before:start', function(options) {
      // Expose config methods globally
      App.getConfig = Module.getConfig;
      App.setConfig = Module.setConfig;

      App.setConfig(options.config);
    });
  });
});
