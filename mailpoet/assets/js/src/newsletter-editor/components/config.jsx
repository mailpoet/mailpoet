import { App } from 'newsletter_editor/App';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore'; // eslint-disable-line func-names

const ConfigComponent = {};

ConfigComponent.ConfigModel = SuperModel.extend({
  defaults: {
    availableStyles: {},
    socialIcons: {},
    blockDefaults: {},
    sidepanelWidth: '331px',
    validation: {},
    urls: {},
    availableDiscountTypes: {},
  },
});

// Global and available styles for access in blocks and their settings
ConfigComponent.config = {};
ConfigComponent.getConfig = function getConfig() {
  return ConfigComponent.config;
};
ConfigComponent.setConfig = function setConfig(options) {
  ConfigComponent.config = new ConfigComponent.ConfigModel(options, {
    parse: true,
  });
  return ConfigComponent.config;
};

App.on('before:start', (BeforeStartApp, options) => {
  const Application = BeforeStartApp;
  const config = _.clone(options.config);
  // Expose config methods globally
  Application.getConfig = ConfigComponent.getConfig;
  Application.setConfig = ConfigComponent.setConfig;

  config.blockDefaults = _.extend(
    config.blockDefaults,
    options.newsletter.body?.blockDefaults || {},
  );

  Application.setConfig(config);
});

export { ConfigComponent };
