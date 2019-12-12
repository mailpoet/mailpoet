import App from 'newsletter_editor/App';
import Marionette from 'backbone.marionette';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore'; // eslint-disable-line func-names
import jQuery from 'jquery';

var Module = {};

Module.StylesModel = SuperModel.extend({
  defaults: {
    text: {
      fontColor: '#000000',
      fontFamily: 'Arial',
      fontSize: '16px',
      lineHeight: '1.6',
    },
    h1: {
      fontColor: '#111111',
      fontFamily: 'Arial',
      fontSize: '40px',
      lineHeight: '1.6',
    },
    h2: {
      fontColor: '#222222',
      fontFamily: 'Tahoma',
      fontSize: '32px',
      lineHeight: '1.6',
    },
    h3: {
      fontColor: '#333333',
      fontFamily: 'Verdana',
      fontSize: '24px',
      lineHeight: '1.6',
    },
    link: {
      fontColor: '#21759B',
      textDecoration: 'underline',
    },
    wrapper: {
      backgroundColor: '#ffffff',
    },
    body: {
      backgroundColor: '#cccccc',
    },
  },
  initialize: function (data) { // eslint-disable-line func-names
    // apply model defaults recursively (not only on top level)
    this.set(jQuery.extend(true, {}, this.defaults, data));
    this.on('change', function () { App.getChannel().trigger('autoSave'); }); // eslint-disable-line func-names
    App.getChannel().on('historyUpdate', this.onHistoryUpdate, this);
  },

  onHistoryUpdate: function onHistoryUpdate(json) {
    this.set(json.globalStyles);
  },
});

Module.StylesView = Marionette.View.extend({
  getTemplate: function () { return window.templates.styles; }, // eslint-disable-line func-names
  templateContext: function () { // eslint-disable-line func-names
    return {
      isWoocommerceTransactional: this.isWoocommerceTransactional,
    };
  },
  modelEvents: {
    change: 'render',
  },
  serializeData: function () { // eslint-disable-line func-names
    return this.model.toJSON();
  },
  initialize: function (options) { // eslint-disable-line func-names
    this.isWoocommerceTransactional = options.isWoocommerceTransactional;
  },
});

Module._globalStyles = new SuperModel();
Module.getGlobalStyles = function () { // eslint-disable-line func-names
  return Module._globalStyles;
};
Module.setGlobalStyles = function (options) { // eslint-disable-line func-names
  Module._globalStyles = new Module.StylesModel(options);
  return Module._globalStyles;
};
Module.getAvailableStyles = function () { // eslint-disable-line func-names
  return App.getConfig().get('availableStyles');
};

App.on('before:start', function (BeforeStartApp, options) { // eslint-disable-line func-names
  var Application = BeforeStartApp;
  var body;
  var globalStyles;
  var overriddenGlobalStyles;
  // Expose style methods to global application
  Application.getGlobalStyles = Module.getGlobalStyles;
  Application.setGlobalStyles = Module.setGlobalStyles;
  Application.getAvailableStyles = Module.getAvailableStyles;

  body = options.newsletter.body;
  globalStyles = (_.has(body, 'globalStyles')) ? body.globalStyles : {};
  overriddenGlobalStyles = (_.has(options.config, 'overrideGlobalStyles')) ? options.config.overrideGlobalStyles : {};
  this.setGlobalStyles(jQuery.extend(true, {}, globalStyles, overriddenGlobalStyles));
});

App.on('start', function (StartApp) { // eslint-disable-line func-names
  var stylesView = new Module.StylesView({
    model: StartApp.getGlobalStyles(),
    isWoocommerceTransactional: App.getNewsletter().isWoocommerceTransactional(),
  });
  StartApp._appView.showChildView('stylesRegion', stylesView);
});

export default Module;
