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
      urls: {}
    }
  });

  // Global and available styles for access in blocks and their settings
  Module._config = {};
  Module.getConfig = function() { return Module._config; };
  Module.setConfig = function(options) {
    Module._config = new Module.ConfigModel(options, { parse: true });
    return Module._config;
  };

  App.on('before:start', function(App, options) {
    var Application = App;
    // Expose config methods globally
    Application.getConfig = Module.getConfig;
    Application.setConfig = Module.setConfig;

    Application.setConfig(options.config);
  });

  return Module;
});
