/* eslint-disable func-names */
import App from 'newsletter_editor/App';
import CommunicationComponent from 'newsletter_editor/components/communication';
import MailPoet from 'mailpoet';
import Marionette from 'backbone.marionette';
import $ from 'jquery';
import Blob from 'blob';
import FileSaver from 'file-saver';
import * as Thumbnail from 'common/thumbnail.ts';
import _ from 'underscore';

var Module = {};
var saveTimeout;
var skipNextAutoSave;

// Save editor contents to server
Module.save = function () {
  var json = App.toJSON();
  var editorTop = $('#mailpoet_editor_top');

  // Stringify to enable transmission of primitive non-string value types
  if (!_.isUndefined(json.body)) {
    if (json.body.blockDefaults) {
      delete json.body.blockDefaults.woocommerceHeading;
      delete json.body.blockDefaults.woocommerceContent;
    }
    json.body = JSON.stringify(json.body);
  }

  App.getChannel().trigger('beforeEditorSave', json);

  // save newsletter
  return CommunicationComponent.saveNewsletter(json).done(function (response) {
    if (response.success !== undefined && response.success === true) {
      // TODO: Handle translations
      // MailPoet.Notice.success("<?php _e('Newsletter has been saved.'); ?>");
    } else if (response.error !== undefined) {
      if (response.error.length === 0) {
        MailPoet.Notice.error(
          MailPoet.I18n.t('templateSaveFailed'),
          {
            positionAfter: editorTop,
            scroll: true,
          }
        );
      } else {
        $(response.error).each(function (i, error) {
          MailPoet.Notice.error(
            error,
            {
              positionAfter: editorTop,
              scroll: true,
            }
          );
        });
      }
    }
    if (!_.isUndefined(json.body)) {
      json.body = JSON.parse(json.body);
    }
    App.getChannel().trigger('afterEditorSave', json, response);
  }).fail(function (response) {
    // TODO: Handle saving errors
    App.getChannel().trigger('afterEditorSave', {}, response);
  });
};

Module.saveTemplate = function (options) {
  return Thumbnail.fromNewsletter(App.toJSON())
    .then(function (thumbnail) {
      var data = _.extend(options || {}, {
        thumbnail: thumbnail,
        body: JSON.stringify(App.getBody()),
        categories: JSON.stringify([
          'saved',
          App.getNewsletter().get('type'),
        ]),
      });

      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'save',
        data: data,
      });
    });
};

Module.exportTemplate = function (options) {
  return Thumbnail.fromNewsletter(App.toJSON())
    .then(function (thumbnail) {
      var data = _.extend(options || {}, {
        thumbnail: thumbnail,
        body: App.getBody(),
        categories: JSON.stringify(['saved', App.getNewsletter().get('type')]),
      });
      var blob = new Blob(
        [JSON.stringify(data)],
        { type: 'application/json;charset=utf-8' }
      );

      FileSaver.saveAs(blob, 'template.json');
      MailPoet.trackEvent('Editor > Template exported', {
        'MailPoet Free version': window.mailpoet_version,
      });
    });
};

Module.SaveView = Marionette.View.extend({
  getTemplate: function () { return window.templates.save; },
  templateContext: function () {
    return {
      wrapperClass: this.wrapperClass,
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
      woocommerceCustomizerEnabled: App.getConfig().get('woocommerceCustomizerEnabled'),
    };
  },
  events: {
    'click .mailpoet_save_button': 'save',
    'click .mailpoet_save_show_options': 'toggleSaveOptions',
    'click .mailpoet_save_next': 'next',
    /* Save as template */
    'click .mailpoet_save_template': 'showSaveAsTemplate',
    'click .mailpoet_save_as_template': 'saveAsTemplate',
    /* Export template */
    'click .mailpoet_save_export': 'showExportTemplate',
    'click .mailpoet_export_template': 'exportTemplate',
    /* WooCommerce */
    'click .mailpoet_save_activate_wc_customizer_button': 'activateWooCommerceCustomizer',
  },

  initialize: function () {
    this.setDropdownDirectionDown();
    App.getChannel().on('beforeEditorSave', this.beforeSave, this);
    App.getChannel().on('afterEditorSave', this.afterSave, this);
  },
  setDropdownDirectionDown: function () {
    this.wrapperClass = 'mailpoet_save_dropdown_down';
  },
  setDropdownDirectionUp: function () {
    this.wrapperClass = 'mailpoet_save_dropdown_up';
  },
  onRender: function () {
    this.validateNewsletter(App.toJSON());
  },
  save: function () {
    this.hideSaveOptions();
    App.getChannel().request('save');
  },
  beforeSave: function () {
    // TODO: Add a loading animation instead
    this.$('.mailpoet_autosaved_at').text(MailPoet.I18n.t('saving'));
  },
  afterSave: function (json) {
    this.validateNewsletter(json);
    // Update 'Last saved timer'
    this.$('.mailpoet_editor_last_saved .mailpoet_autosaved_message').removeClass('mailpoet_hidden');
    this.$('.mailpoet_autosaved_at').text('');
  },
  showSaveOptions: function () {
    this.$('.mailpoet_save_show_options').addClass('mailpoet_save_show_options_active');
    this.$('.mailpoet_save_options').removeClass('mailpoet_hidden');
    this.hideSaveAsTemplate();
    this.hideExportTemplate();
  },
  hideSaveOptions: function () {
    this.$('.mailpoet_save_show_options').removeClass('mailpoet_save_show_options_active');
    this.$('.mailpoet_save_options').addClass('mailpoet_hidden');
    this.hideSaveAsTemplate();
    this.hideExportTemplate();
  },
  toggleSaveOptions: function () {
    if (this.$('.mailpoet_save_show_options').hasClass('mailpoet_save_show_options_active')) {
      this.hideSaveOptions();
    } else {
      this.showSaveOptions();
    }
  },
  showSaveAsTemplate: function () {
    this.$('.mailpoet_save_as_template_container').removeClass('mailpoet_hidden');
  },
  hideSaveAsTemplate: function () {
    this.$('.mailpoet_save_as_template_container').addClass('mailpoet_hidden');
  },
  saveAsTemplate: function () {
    var templateName = this.$('.mailpoet_save_as_template_name').val();
    var editorTop = $('#mailpoet_editor_top');

    if (templateName === '') {
      MailPoet.Notice.error(
        MailPoet.I18n.t('templateNameMissing'),
        {
          positionAfter: editorTop,
          scroll: true,
        }
      );
    } else {
      Module.saveTemplate({
        name: templateName,
      }).then(function () {
        MailPoet.Notice.success(
          MailPoet.I18n.t('templateSaved'),
          {
            positionAfter: editorTop,
            scroll: true,
          }
        );
        MailPoet.trackEvent('Editor > Template saved', {
          'MailPoet Free version': window.mailpoet_version,
        });
      }).catch(function () {
        MailPoet.Notice.error(
          MailPoet.I18n.t('templateSaveFailed'),
          {
            positionAfter: editorTop,
            scroll: true,
          }
        );
      });
      this.hideSaveOptions();
    }
  },
  showExportTemplate: function () {
    this.$('.mailpoet_export_template_container').removeClass('mailpoet_hidden');
  },
  hideExportTemplate: function () {
    this.$('.mailpoet_export_template_container').addClass('mailpoet_hidden');
  },
  exportTemplate: function () {
    var templateName = this.$('.mailpoet_export_template_name').val();
    var editorTop = $('#mailpoet_editor_top');

    if (templateName === '') {
      MailPoet.Notice.error(
        MailPoet.I18n.t('templateNameMissing'),
        {
          positionAfter: editorTop,
          scroll: true,
        }
      );
    } else {
      Module.exportTemplate({
        name: templateName,
      });
      this.hideExportTemplate();
    }
  },
  next: function () {
    this.hideSaveOptions();
    if (!this.$('.mailpoet_save_next').hasClass('button-disabled')) {
      Module._cancelAutosave();
      Module.save().done(function () {
        window.location.href = App.getConfig().get('urls.send');
      });
    }
  },
  validateNewsletter: function (jsonObject) {
    var body = '';
    var newsletter = App.getNewsletter();
    var content;
    if (!App._contentContainer.isValid()) {
      this.showValidationError(App._contentContainer.validationError);
      return;
    }

    if (jsonObject && jsonObject.body && jsonObject.body.content) {
      content = jsonObject.body.content;
      body = JSON.stringify(jsonObject.body.content);
      if (!content.blocks || !Array.isArray(content.blocks) || (content.blocks.length === 0)) {
        this.showValidationError(MailPoet.I18n.t('newsletterIsEmpty'));
        return;
      }
    }
    if (App.getConfig().get('validation.validateUnsubscribeLinkPresent')
        && body.indexOf('[link:subscription_unsubscribe_url]') < 0
        && body.indexOf('[link:subscription_unsubscribe]') < 0
        && newsletter.get('status') !== 'sent') {
      this.showValidationError(MailPoet.I18n.t('unsubscribeLinkMissing'));
      return;
    }

    if ((newsletter.get('type') === 'notification')
        && body.indexOf('"type":"automatedLatestContent"') < 0
        && body.indexOf('"type":"automatedLatestContentLayout"') < 0
    ) {
      this.showValidationError(MailPoet.I18n.t('automatedLatestContentMissing'));
      return;
    }

    if (newsletter.get('type') === 'standard' && newsletter.get('status') === 'sent') {
      this.showValidationError(MailPoet.I18n.t('emailAlreadySent'));
      return;
    }

    this.hideValidationError();
  },
  showValidationError: function (message) {
    var $el = this.$('.mailpoet_save_error');
    $el.html(message.replace(/\. /g, '.<br>'));
    $el.removeClass('mailpoet_hidden');

    this.$('.mailpoet_save_next').addClass('button-disabled');
  },
  hideValidationError: function () {
    this.$('.mailpoet_save_error').addClass('mailpoet_hidden');
    this.$('.mailpoet_save_next').removeClass('button-disabled');
  },
  activateWooCommerceCustomizer: function () {
    var $el = $('.mailpoet_save_woocommerce_customizer_disabled');
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data: {
        'woocommerce.use_mailpoet_editor': 1,
      },
    }).done(function () {
      $el.addClass('mailpoet_hidden');
      MailPoet.trackEvent('Editor > WooCommerce email customizer enabled', {
        'MailPoet Free version': window.mailpoet_version,
      });
    }).fail(function (response) {
      MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
    });
  },
});

Module.autoSave = function () {
  // Delay in saving editor contents, during which a new autosave
  // may be requested
  var AUTOSAVE_DELAY_DURATION = 1000;

  Module._cancelAutosave();
  saveTimeout = setTimeout(function () {
    if (skipNextAutoSave) {
      skipNextAutoSave = false;
      Module._cancelAutosave();
      return;
    }
    App.getChannel().request('save').always(function () {
      Module._cancelAutosave();
    });
  }, AUTOSAVE_DELAY_DURATION);
};

Module._cancelAutosave = function () {
  if (!saveTimeout) return;

  clearTimeout(saveTimeout);
  saveTimeout = undefined;
};

Module.onHistoryUpdate = function onHistoryUpdate() {
  skipNextAutoSave = true;
};

Module.beforeExitWithUnsavedChanges = function (e) {
  var message;
  var event;
  if (saveTimeout) {
    message = MailPoet.I18n.t('unsavedChangesWillBeLost');
    event = e || window.event;

    if (event) {
      event.returnValue = message;
    }

    return message;
  }
  return undefined;
};

App.on('before:start', function (BeforeStartApp) {
  var Application = BeforeStartApp;
  Application.save = Module.save;
  Application.getChannel().on('autoSave', Module.autoSave);
  Application.getChannel().on('historyUpdate', Module.onHistoryUpdate);

  window.onbeforeunload = Module.beforeExitWithUnsavedChanges;

  Application.getChannel().reply('save', Application.save);
});

App.on('start', function (BeforeStartApp) {
  var model = BeforeStartApp.getNewsletter();
  var topSaveView = new Module.SaveView({ model: model });
  var bottomSaveView = new Module.SaveView({ model: model });
  bottomSaveView.setDropdownDirectionUp();

  BeforeStartApp._appView.showChildView('topRegion', topSaveView);
  BeforeStartApp._appView.showChildView('bottomRegion', bottomSaveView);
});

export default Module;
