define([
    'newsletter_editor/App',
    'backbone.supermodel',
    'underscore'
  ], function(App, SuperModel, _) {
  "use strict";

  var Module = {};

  // Holds newsletter entry fields, such as subject or creation datetime.
  // Does not hold newsletter content nor newsletter styles, those are
  // handled by other components.
  Module.NewsletterModel = SuperModel.extend({
    stale: ['body'],
    initialize: function(options) {
      this.on('change', function() {
          App.getChannel().trigger('autoSave');
      });
    },
    toJSON: function() {
      // Remove stale attributes from resulting JSON object
      return _.omit(SuperModel.prototype.toJSON.call(this), this.stale);
    },
  });

  // Content block view and model handlers for different content types
  Module._blockTypes = {};
  Module.registerBlockType = function(type, data) {
    Module._blockTypes[type] = data;
  };
  Module.getBlockTypeModel = function(type) {
    if (type in Module._blockTypes) {
      return Module._blockTypes[type].blockModel;
    } else {
      throw "Block type not supported: " + type;
    }
  };
  Module.getBlockTypeView = function(type) {
    if (type in Module._blockTypes) {
      return Module._blockTypes[type].blockView;
    } else {
      throw "Block type not supported: " + type;
    }
  };

  Module.getBody = function() {
    return JSON.stringify({
      content: App._contentContainer.toJSON(),
      globalStyles: App.getGlobalStyles().toJSON(),
    });
  };

  Module.toJSON = function() {
    return _.extend({
      body: Module.getBody(),
    }, App.getNewsletter().toJSON());
  };

  Module.getNewsletter = function() {
      return Module.newsletter;
  };

  App.on('before:start', function(options) {
    // Expose block methods globally
    App.registerBlockType = Module.registerBlockType;
    App.getBlockTypeModel = Module.getBlockTypeModel;
    App.getBlockTypeView = Module.getBlockTypeView;
    App.toJSON = Module.toJSON;
    App.getBody = Module.getBody;
    App.getNewsletter = Module.getNewsletter;

    Module.newsletter = new Module.NewsletterModel(_.omit(_.clone(options.newsletter), ['body']));
  });

  App.on('start', function(options) {
    // TODO: Other newsletter information will be needed as well.
    var body = JSON.parse(options.newsletter.body);
    App._contentContainer = new (App.getBlockTypeModel('container'))(body.content, {parse: true});
    App._contentContainerView = new (App.getBlockTypeView('container'))({
      model: App._contentContainer,
      renderOptions: { depth: 0 },
    });

    App._appView.contentRegion.show(App._contentContainerView);
  });


  return Module;
});
