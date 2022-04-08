import App from 'newsletter_editor/App';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore'; // eslint-disable-line func-names

const Module = {};

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
Module.config = {};
Module.getConfig = function getConfig() {
  return Module.config;
};
Module.setConfig = function setConfig(options) {
  Module.config = new Module.ConfigModel(options, { parse: true });
  return Module.config;
};

App.on('before:start', (BeforeStartApp, options) => {
  const Application = BeforeStartApp;
  const config = _.clone(options.config);
  // Expose config methods globally
  Application.getConfig = Module.getConfig;
  Application.setConfig = Module.setConfig;

  config.blockDefaults = _.extend(
    config.blockDefaults,
    options.newsletter.body?.blockDefaults || {},
  );

  Application.setConfig(config);
});

export default Module;
