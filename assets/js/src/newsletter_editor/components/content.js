'use strict';

define([
  'newsletter_editor/App',
  'backbone.supermodel',
  'underscore',
  'mailpoet'
], function (App, SuperModel, _, MailPoet) { // eslint-disable-line func-names
  var Module = {};

  // Holds newsletter entry fields, such as subject or creation datetime.
  // Does not hold newsletter content nor newsletter styles, those are
  // handled by other components.
  Module.NewsletterModel = SuperModel.extend({
    whitelisted: ['id', 'subject', 'preheader', 'type'],
    initialize: function () { // eslint-disable-line func-names
      this.on('change', function () { // eslint-disable-line func-names
        App.getChannel().trigger('autoSave');
      });
    },
    toJSON: function () { // eslint-disable-line func-names
      // Use only whitelisted properties to ensure properties editor
      // doesn't control don't change.
      return _.pick(SuperModel.prototype.toJSON.call(this), this.whitelisted);
    }
  });

  // Content block view and model handlers for different content types
  Module._blockTypes = {};
  Module.registerBlockType = function (type, data) { // eslint-disable-line func-names
    Module._blockTypes[type] = data;
  };
  Module.getBlockTypeModel = function (type) { // eslint-disable-line func-names
    if (type in Module._blockTypes) {
      return Module._blockTypes[type].blockModel;
    }
    throw 'Block type not supported: ' + type;
  };
  Module.getBlockTypeView = function (type) { // eslint-disable-line func-names
    if (type in Module._blockTypes) {
      return Module._blockTypes[type].blockView;
    }
    throw 'Block type not supported: ' + type;
  };

  Module.getBody = function () { // eslint-disable-line func-names
    return {
      content: App._contentContainer.toJSON(),
      globalStyles: App.getGlobalStyles().toJSON()
    };
  };

  Module.toJSON = function () { // eslint-disable-line func-names
    return _.extend({
      body: Module.getBody()
    }, App.getNewsletter().toJSON());
  };

  Module.getNewsletter = function () { // eslint-disable-line func-names
    return Module.newsletter;
  };

  Module.findModels = function (predicate) { // eslint-disable-line func-names
    var blocks = App._contentContainer.getChildren();
    return _.filter(blocks, predicate);
  };

  App.on('before:start', function (Application, options) { // eslint-disable-line func-names
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

  App.on('start', function (Application, options) { // eslint-disable-line func-names
    var StartApp = Application;
    var body = options.newsletter.body;
    var content = (_.has(body, 'content')) ? body.content : {};

    if (!_.has(options.newsletter, 'body') || !_.isObject(options.newsletter.body)) {
      MailPoet.Notice.error(
        MailPoet.I18n.t('newsletterBodyIsCorrupted'),
        { static: true }
      );
    }

    StartApp._contentContainer = new (StartApp.getBlockTypeModel('container'))(content, { parse: true });
    StartApp._contentContainerView = new (StartApp.getBlockTypeView('container'))({
      model: StartApp._contentContainer,
      renderOptions: { depth: 0 }
    });

    StartApp._appView.showChildView('contentRegion', StartApp._contentContainerView);
  });


  return Module;
});
