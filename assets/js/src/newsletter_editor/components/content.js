define([
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
  ], function(EditorApplication, Backbone, Marionette) {

  EditorApplication.module("components.content", function(Module, App, Backbone, Marionette, $, _) {
    "use strict";

    // Holds newsletter entry fields, such as subject or creation datetime.
    // Does not hold newsletter content nor newsletter styles, those are
    // handled by other components.
    Module.NewsletterModel = Backbone.SuperModel.extend({
      stale: ['data', 'styles'],
      initialize: function(options) {
        this.on('change', function() {
            App.getChannel().trigger('autoSave');
        });
      },
      toJSON: function() {
        // Remove stale attributes from resulting JSON object
        return _.omit(Backbone.SuperModel.prototype.toJSON.call(this), this.stale);
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

    Module.toJSON = function() {
      return _.extend({
        data: App._contentContainer.toJSON(),
        styles: App.getGlobalStyles().toJSON(),
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
      App.getNewsletter = Module.getNewsletter;

      Module.newsletter = new Module.NewsletterModel(_.omit(_.clone(options.newsletter), ['data', 'styles']));
    });

    App.on('start', function(options) {
      // TODO: Other newsletter information will be needed as well.
      App._contentContainer = new (this.getBlockTypeModel('container'))(options.newsletter.data, {parse: true});
      App._contentContainerView = new (this.getBlockTypeView('container'))({
        model: App._contentContainer,
        renderOptions: { depth: 0 },
      });

      App._appView.contentRegion.show(App._contentContainerView);
    });
  });
});
