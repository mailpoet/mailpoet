define([
  'newsletter_editor/App',
  'backbone.marionette',
  'backbone.supermodel',
  'underscore'
], function(App, Marionette, SuperModel, _) {

  'use strict';

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
    initialize: function() {
      this.on('change', function() { App.getChannel().trigger('autoSave'); });
    }
  });

  Module.StylesView = Marionette.View.extend({
    getTemplate: function() { return window.templates.styles; },
    modelEvents: {
      change: 'render'
    },
    serializeData: function() {
      return this.model.toJSON();
    }
  });

  Module._globalStyles = new SuperModel();
  Module.getGlobalStyles = function() {
    return Module._globalStyles;
  };
  Module.setGlobalStyles = function(options) {
    Module._globalStyles = new Module.StylesModel(options);
    return Module._globalStyles;
  };
  Module.getAvailableStyles = function() {
    return App.getConfig().get('availableStyles');
  };

  App.on('before:start', function(App, options) {
    var Application = App;
    // Expose style methods to global application
    Application.getGlobalStyles = Module.getGlobalStyles;
    Application.setGlobalStyles = Module.setGlobalStyles;
    Application.getAvailableStyles = Module.getAvailableStyles;

    var body = options.newsletter.body;
    var globalStyles = (_.has(body, 'globalStyles')) ? body.globalStyles : {};
    this.setGlobalStyles(globalStyles);
  });

  App.on('start', function(App, options) {
    var stylesView = new Module.StylesView({ model: App.getGlobalStyles() });
    App._appView.showChildView('stylesRegion', stylesView);
  });

  return Module;
});
