import App from 'newsletter_editor/App';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore'; // eslint-disable-line func-names

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
Module.getConfig = function () { return Module._config; }; // eslint-disable-line func-names
Module.setConfig = function (options) { // eslint-disable-line func-names
  Module._config = new Module.ConfigModel(options, { parse: true });
  return Module._config;
};

App.on('before:start', function (BeforeStartApp, options) { // eslint-disable-line func-names
  var Application = BeforeStartApp;
  var config = _.clone(options.config);
  // Expose config methods globally
  Application.getConfig = Module.getConfig;
  Application.setConfig = Module.setConfig;

  config.blockDefaults = _.extend(
    config.blockDefaults,
    options.newsletter.body.blockDefaults || {}
  );

  Application.setConfig(config);
});

export default Module;
