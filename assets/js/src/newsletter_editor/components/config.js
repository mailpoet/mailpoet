define([
    'newsletter_editor/App',
    'backbone.supermodel'
  ], function(App, SuperModel) {

  var Module = {};

  Module.ConfigModel = SuperModel.extend({
    defaults: {
      availableStyles: {},
      socialIcons: {},
      blockDefaults: {},
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

  App.on('before:start', function(App, options) {
    // Expose config methods globally
    App.getConfig = Module.getConfig;
    App.setConfig = Module.setConfig;

    App.setConfig(options.config);
  });

  return Module;
});
