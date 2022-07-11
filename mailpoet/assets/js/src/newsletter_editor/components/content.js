import { App } from 'newsletter_editor/App';
import SuperModel from 'backbone.supermodel';
import _ from 'underscore';
import { MailPoet } from 'mailpoet';

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
  },
  isWoocommerceTransactional: function isWoocommerceTransactional() {
    return this.get('type') === 'wc_transactional';
  },
  isAutomationEmail: function isAutomationEmail() {
    return this.get('type') === 'automation';
  },
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
  return Module._blockTypes.unknownBlockFallback.blockModel;
};
Module.getBlockTypeView = function getBlockTypeView(type) {
  if (type in Module._blockTypes) {
    return Module._blockTypes[type].blockView;
  }
  return Module._blockTypes.unknownBlockFallback.blockView;
};

Module.getBody = function getBody() {
  return {
    content: App._contentContainer.toJSON(),
    globalStyles: App.getGlobalStyles().toJSON(),
    blockDefaults: _.omit(
      App.getConfig().toJSON().blockDefaults,
      'text',
      'image',
    ),
  };
};

Module.toJSON = function toJSON() {
  return _.extend(
    {
      body: Module.getBody(),
    },
    App.getNewsletter().toJSON(),
  );
};

Module.getNewsletter = function getNewsletter() {
  return Module.newsletter;
};

Module.findModels = function findModels(predicate) {
  var blocks = App._contentContainer.getChildren();
  return _.filter(blocks, predicate);
};

Module.renderContent = function renderContent(content) {
  if (App._contentContainer) {
    App._contentContainer.destroy();
  }
  if (App._contentContainerView) {
    App._contentContainerView.destroy();
  }
  App._contentContainer = new (App.getBlockTypeModel('container'))(content, {
    parse: true,
  });
  App._contentContainerView = new (App.getBlockTypeView('container'))({
    model: App._contentContainer,
    renderOptions: { depth: 0 },
  });
  App._appView.showChildView('contentRegion', App._contentContainerView);
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

  Module.newsletter = new Module.NewsletterModel(
    _.omit(_.clone(options.newsletter), ['body']),
  );
});

App.on('start', function appOnStart(Application, options) {
  var StartApp = Application;
  var body = options.newsletter.body;
  var bodyContent = _.has(body, 'content') ? body.content : {};

  if (
    !_.has(options.newsletter, 'body') ||
    !_.isObject(options.newsletter.body)
  ) {
    MailPoet.Notice.error(MailPoet.I18n.t('newsletterBodyIsCorrupted'), {
      static: true,
    });
  }
  Module.renderContent(bodyContent);

  StartApp.getChannel().on(
    'historyUpdate',
    function onHistoryUpdate(json) {
      Module.renderContent(json.content);
    },
    this,
  );
});

export { Module as ContentComponent };
