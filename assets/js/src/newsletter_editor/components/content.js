'use strict';

define([
  'newsletter_editor/App',
  'backbone.supermodel',
  'underscore',
  'mailpoet'
], function content(App, SuperModel, _, MailPoet) {
  var Module = {};

  // Holds newsletter entry fields, such as subject or creation datetime.
  // Does not hold newsletter content nor newsletter styles, those are
  // handled by other components.
  Module.NewsletterModel = SuperModel.extend({
    whitelisted: ['id', 'subject', 'preheader', 'type'],
    initialize: function initialize() {
      this.on('change', function onChange() {
        App.getChannel().trigger('autoSave');
      });
    },
    toJSON: function toJSON() {
      // Use only whitelisted properties to ensure properties editor
      // doesn't control don't change.
      return _.pick(SuperModel.prototype.toJSON.call(this), this.whitelisted);
    }
  });

  // Content block view and model handlers for different content types
  Module._blockTypes = {};
  Module.registerBlockType = function registerBlockType(type, data) {
    Module._blockTypes[type] = data;
  };
  Module.getBlockTypeModel = function getBlockTypeModel(type) {
    if (type in Module._blockTypes) {
      return Module._blockTypes[type].blockModel;
    }
    throw new Error('Block type not supported: ' + type);
  };
  Module.getBlockTypeView = function getBlockTypeView(type) {
    if (type in Module._blockTypes) {
      return Module._blockTypes[type].blockView;
    }
    throw new Error('Block type not supported: ' + type);
  };

  Module.getBody = function getBody() {
    return {
      content: App._contentContainer.toJSON(),
      globalStyles: App.getGlobalStyles().toJSON(),
      blockDefaults: _.omit(App.getConfig().toJSON().blockDefaults, 'text', 'image')
    };
  };

  Module.toJSON = function toJSON() {
    return _.extend({
      body: Module.getBody()
    }, App.getNewsletter().toJSON());
  };

  Module.getNewsletter = function getNewsletter() {
    return Module.newsletter;
  };

  Module.findModels = function findModels(predicate) {
    var blocks = App._contentContainer.getChildren();
    return _.filter(blocks, predicate);
  };

  App.on('before:start', function appBeforeStart(Application, options) {
    var BeforeStartApp = Application;
    // Expose block methods globally
    BeforeStartApp.registerBlockType = Module.registerBlockType;
    BeforeStartApp.getBlockTypeModel = Module.getBlockTypeModel;
    BeforeStartApp.getBlockTypeView = Module.getBlockTypeView;
    BeforeStartApp.toJSON = Module.toJSON;
    BeforeStartApp.getBody = Module.getBody;
    BeforeStartApp.getNewsletter = Module.getNewsletter;
    BeforeStartApp.findModels = Module.findModels;

    Module.newsletter = new Module.NewsletterModel(_.omit(_.clone(options.newsletter), ['body']));
  });

  App.on('start', function appOnStart(Application, options) {
    var StartApp = Application;
    var body = options.newsletter.body;
    var bodyContent = (_.has(body, 'content')) ? body.content : {};

    if (!_.has(options.newsletter, 'body') || !_.isObject(options.newsletter.body)) {
      MailPoet.Notice.error(
        MailPoet.I18n.t('newsletterBodyIsCorrupted'),
        { static: true }
      );
    }

    StartApp._contentContainer = new (StartApp.getBlockTypeModel('container'))(bodyContent, { parse: true });
    StartApp._contentContainerView = new (StartApp.getBlockTypeView('container'))({
      model: StartApp._contentContainer,
      renderOptions: { depth: 0 }
    });

    StartApp._appView.showChildView('contentRegion', StartApp._contentContainerView);
  });


  return Module;
});
