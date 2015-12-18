define([
    'newsletter_editor/App',
    'backbone.marionette',
    'backbone.supermodel'
  ], function(App, Marionette, SuperModel) {

  "use strict";

  var Module = {};

  Module.StylesModel = SuperModel.extend({
    defaults: {
      text: {
        fontColor: '#000000',
        fontFamily: 'Arial',
        fontSize: '16px',
      },
      h1: {
        fontColor: '#111111',
        fontFamily: 'Arial Black',
        fontSize: '40px'
      },
      h2: {
        fontColor: '#222222',
        fontFamily: 'Tahoma',
        fontSize: '32px',
      },
      h3: {
        fontColor: '#333333',
        fontFamily: 'Verdana',
        fontSize: '24px',
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
    initialize: function() {
      this.on('change', function() { App.getChannel().trigger('autoSave'); });
    },
  });

  Module.StylesView = Marionette.ItemView.extend({
    getTemplate: function() { return templates.styles; },
    modelEvents: {
      'change': 'render',
    },
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

  App.on('before:start', function(options) {
    // Expose style methods to global application
    App.getGlobalStyles = Module.getGlobalStyles;
    App.setGlobalStyles = Module.setGlobalStyles;

    App.getAvailableStyles = Module.getAvailableStyles;

    var body = options.newsletter.body;
    this.setGlobalStyles(body.globalStyles);
  });

  App.on('start', function(options) {
    var stylesView = new Module.StylesView({ model: App.getGlobalStyles() });
    App._appView.stylesRegion.show(stylesView);
  });

  return Module;
});
