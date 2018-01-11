
'use strict';

define([
  'newsletter_editor/App',
  'backbone.marionette',
  'backbone.supermodel',
  'underscore'
], function (App, Marionette, SuperModel, _) { // eslint-disable-line func-names
  var Module = {};

  Module.StylesModel = SuperModel.extend({
    defaults: {
      text: {
        fontColor: '#000000',
        fontFamily: 'Arial',
        fontSize: '16px'
      },
      h1: {
        fontColor: '#111111',
        fontFamily: 'Arial',
        fontSize: '40px'
      },
      h2: {
        fontColor: '#222222',
        fontFamily: 'Tahoma',
        fontSize: '32px'
      },
      h3: {
        fontColor: '#333333',
        fontFamily: 'Verdana',
        fontSize: '24px'
      },
      link: {
        fontColor: '#21759B',
        textDecoration: 'underline'
      },
      wrapper: {
        backgroundColor: '#ffffff'
      },
      body: {
        backgroundColor: '#cccccc'
      }
    },
    initialize: function () { // eslint-disable-line func-names
      this.on('change', function () { App.getChannel().trigger('autoSave'); }); // eslint-disable-line func-names
    }
  });

  Module.StylesView = Marionette.View.extend({
    getTemplate: function () { return window.templates.styles; }, // eslint-disable-line func-names
    modelEvents: {
      change: 'render'
    },
    serializeData: function () { // eslint-disable-line func-names
      return this.model.toJSON();
    }
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
    // Expose style methods to global application
    Application.getGlobalStyles = Module.getGlobalStyles;
    Application.setGlobalStyles = Module.setGlobalStyles;
    Application.getAvailableStyles = Module.getAvailableStyles;

    body = options.newsletter.body;
    globalStyles = (_.has(body, 'globalStyles')) ? body.globalStyles : {};
    this.setGlobalStyles(globalStyles);
  });

  App.on('start', function (StartApp) { // eslint-disable-line func-names
    var stylesView = new Module.StylesView({ model: StartApp.getGlobalStyles() });
    StartApp._appView.showChildView('stylesRegion', stylesView);
  });

  return Module;
});
