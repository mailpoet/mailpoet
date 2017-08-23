define([
    'newsletter_editor/App',
    'newsletter_editor/components/communication',
    'mailpoet',
    'notice',
    'backbone',
    'backbone.marionette',
    'jquery',
    'blob',
    'file-saver',
    'html2canvas'
  ], function(
    App,
    CommunicationComponent,
    MailPoet,
    Notice,
    Backbone,
    Marionette,
    jQuery,
    Blob,
    FileSaver,
    html2canvas
  ) {

  'use strict';

  var Module = {},
      saveTimeout;

  // Save editor contents to server
  Module.save = function() {

    var json = App.toJSON();

    // Stringify to enable transmission of primitive non-string value types
    if (!_.isUndefined(json.body)) {
      json.body = JSON.stringify(json.body);
    }

    App.getChannel().trigger('beforeEditorSave', json);

    // save newsletter
    return CommunicationComponent.saveNewsletter(json).done(function(response) {
      if(response.success !== undefined && response.success === true) {
        // TODO: Handle translations
        //MailPoet.Notice.success("<?php _e('Newsletter has been saved.'); ?>");
      } else if(response.error !== undefined) {
        if(response.error.length === 0) {
          MailPoet.Notice.error(
            MailPoet.I18n.t('templateSaveFailed'),
            {
              scroll: true
            }
          );
        } else {
          $(response.error).each(function(i, error) {
            MailPoet.Notice.error(error, { scroll: true });
          });
        }
      }
      App.getChannel().trigger('afterEditorSave', json, response);
    }).fail(function(response) {
      // TODO: Handle saving errors
      App.getChannel().trigger('afterEditorSave', {}, response);
    });
  };

  // For getting a promise after triggering save event
  Module.saveAndProvidePromise = function(saveResult) {
    var result = saveResult;
    var promise = Module.save();
    if (saveResult !== undefined) {
      result.promise = promise;
    }
  };

  Module.getThumbnail = function(element, options) {
    var promise = html2canvas(element, options || {});

    return promise.then(function(oldCanvas) {
      // Temporary workaround for html2canvas-alpha2.
      // Removes 1px left transparent border from resulting canvas.

      var oldContext = oldCanvas.getContext('2d'),
          newCanvas = document.createElement('canvas'),
          newContext = newCanvas.getContext('2d'),
          leftBorderWidth = 1;

      newCanvas.width = oldCanvas.width;
      newCanvas.height = oldCanvas.height;

      newContext.drawImage(
        oldCanvas,
        leftBorderWidth, 0, oldCanvas.width - leftBorderWidth, oldCanvas.height,
        0, 0, oldCanvas.width, oldCanvas.height
      );

      return newCanvas;
    });
  };

  Module.saveTemplate = function(options) {
    var that = this,
        promise = jQuery.Deferred();

    promise.then(function(thumbnail) {
      var data = _.extend(options || {}, {
        thumbnail: thumbnail.toDataURL('image/jpeg'),
        body: JSON.stringify(App.getBody())
      });

      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletterTemplates',
        action: 'save',
        data: data
      });
    });

    Module.getThumbnail(
      jQuery('#mailpoet_editor_content > .mailpoet_block').get(0)
    ).then(function(thumbnail) {
      promise.resolve(thumbnail);
    });

    return promise;
  };

  Module.exportTemplate = function(options) {
    var that = this;
    return Module.getThumbnail(
      jQuery('#mailpoet_editor_content > .mailpoet_block').get(0)
    ).then(function(thumbnail) {
      var data = _.extend(options || {}, {
        thumbnail: thumbnail.toDataURL('image/jpeg'),
        body: App.getBody()
      });
      var blob = new Blob(
        [JSON.stringify(data)],
        { type: 'application/json;charset=utf-8' }
      );

      FileSaver.saveAs(blob, 'template.json');
      MailPoet.trackEvent('Editor > Template exported', {
        'MailPoet Free version': window.mailpoet_version
      });
    });
  };

  Module.SaveView = Marionette.View.extend({
    getTemplate: function() { return templates.save; },
    events: {
      'click .mailpoet_save_button': 'save',
      'click .mailpoet_save_show_options': 'toggleSaveOptions',
      'click .mailpoet_save_next': 'next',
      /* Save as template */
      'click .mailpoet_save_template': 'toggleSaveAsTemplate',
      'click .mailpoet_save_as_template': 'saveAsTemplate',
      /* Export template */
      'click .mailpoet_save_export': 'toggleExportTemplate',
      'click .mailpoet_export_template': 'exportTemplate'
    },
    initialize: function(options) {
      App.getChannel().on('beforeEditorSave', this.beforeSave, this);
      App.getChannel().on('afterEditorSave', this.afterSave, this);
    },
    onRender: function() {
      this.validateNewsletter(App.toJSON());
    },
    save: function() {
      this.hideOptionContents();
      App.getChannel().trigger('save');
    },
    beforeSave: function() {
      // TODO: Add a loading animation instead
      this.$('.mailpoet_autosaved_at').text(MailPoet.I18n.t('saving'));
    },
    afterSave: function(json, response) {
      this.validateNewsletter(json);
      // Update 'Last saved timer'
      this.$('.mailpoet_editor_last_saved').removeClass('mailpoet_hidden');
      this.$('.mailpoet_autosaved_at').text('');
    },
    toggleSaveOptions: function() {
      this.$('.mailpoet_save_options').toggleClass('mailpoet_hidden');
      this.$('.mailpoet_save_show_options').toggleClass('mailpoet_save_show_options_active');
    },
    toggleSaveAsTemplate: function() {
      this.$('.mailpoet_save_as_template_container').toggleClass('mailpoet_hidden');
      this.toggleSaveOptions();
    },
    showSaveAsTemplate: function() {
      this.$('.mailpoet_save_as_template_container').removeClass('mailpoet_hidden');
      this.toggleSaveOptions();
    },
    hideSaveAsTemplate: function() {
      this.$('.mailpoet_save_as_template_container').addClass('mailpoet_hidden');
    },
    saveAsTemplate: function() {
      var templateName = this.$('.mailpoet_save_as_template_name').val(),
          templateDescription = this.$('.mailpoet_save_as_template_description').val(),
          that = this;

      if (templateName === '') {
        MailPoet.Notice.error(
          MailPoet.I18n.t('templateNameMissing'),
          {
            positionAfter: that.$el,
            scroll: true
          }
        );
      } else if (templateDescription === '') {
        MailPoet.Notice.error(
          MailPoet.I18n.t('templateDescriptionMissing'),
          {
            positionAfter: that.$el,
            scroll: true
          }
        );
      } else {
        Module.saveTemplate({
          name: templateName,
          description: templateDescription
        }).done(function() {
          MailPoet.Notice.success(
            MailPoet.I18n.t('templateSaved'),
            {
              positionAfter: that.$el,
              scroll: true
            }
          );
          MailPoet.trackEvent('Editor > Template saved', {
            'MailPoet Free version': window.mailpoet_version
          });
        }).fail(function() {
          MailPoet.Notice.error(
            MailPoet.I18n.t('templateSaveFailed'),
            {
              positionAfter: that.$el,
              scroll: true
            }
          );
        });
        this.hideOptionContents();
      }

    },
    toggleExportTemplate: function() {
      this.$('.mailpoet_export_template_container').toggleClass('mailpoet_hidden');
      this.toggleSaveOptions();
    },
    hideExportTemplate: function() {
      this.$('.mailpoet_export_template_container').addClass('mailpoet_hidden');
    },
    exportTemplate: function() {
      var templateName = this.$('.mailpoet_export_template_name').val(),
          templateDescription = this.$('.mailpoet_export_template_description').val(),
          that = this;

      if (templateName === '') {
        MailPoet.Notice.error(
          MailPoet.I18n.t('templateNameMissing'),
          {
            positionAfter: that.$el,
            scroll: true
          }
        );
      } else if (templateDescription === '') {
        MailPoet.Notice.error(
          MailPoet.I18n.t('templateDescriptionMissing'),
          {
            positionAfter: that.$el,
            scroll: true
          }
        );
      } else {
        Module.exportTemplate({
          name: templateName,
          description: templateDescription
        });
        this.hideExportTemplate();
      }
    },
    hideOptionContents: function() {
      this.hideSaveAsTemplate();
      this.hideExportTemplate();
      this.$('.mailpoet_save_options').addClass('mailpoet_hidden');
    },
    next: function() {
      this.hideOptionContents();
      if(!this.$('.mailpoet_save_next').hasClass('button-disabled')) {
        window.location.href = App.getConfig().get('urls.send');
      }
    },
    validateNewsletter: function(jsonObject) {
      if (!App._contentContainer.isValid()) {
        this.showValidationError(App._contentContainer.validationError);
        return;
      }

      var contents = JSON.stringify(jsonObject);
      if (App.getConfig().get('validation.validateUnsubscribeLinkPresent') &&
          contents.indexOf('[link:subscription_unsubscribe_url]') < 0 &&
          contents.indexOf('[link:subscription_unsubscribe]') < 0) {
        this.showValidationError(MailPoet.I18n.t('unsubscribeLinkMissing'));
        return;
      }

      this.hideValidationError();
    },
    showValidationError: function(message) {
      var $el = this.$('.mailpoet_save_error');
      $el.text(message);
      $el.removeClass('mailpoet_hidden');

      this.$('.mailpoet_save_next').addClass('button-disabled');
    },
    hideValidationError: function() {
      this.$('.mailpoet_save_error').addClass('mailpoet_hidden');
      this.$('.mailpoet_save_next').removeClass('button-disabled');
    }
  });

  Module.autoSave = function() {
    // Delay in saving editor contents, during which a new autosave
    // may be requested
    var AUTOSAVE_DELAY_DURATION = 1000;

    // Cancel save timer if another change happens before it completes
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(function() {
      App.getChannel().trigger('save');
      clearTimeout(saveTimeout);
      saveTimeout = undefined;
    }, AUTOSAVE_DELAY_DURATION);
  };

  Module.beforeExitWithUnsavedChanges = function(e) {
    if (saveTimeout) {
      var message = MailPoet.I18n.t('unsavedChangesWillBeLost');
      var event = e || window.event;

      if (event) {
        event.returnValue = message;
      }

      return message;
    }
  };

  App.on('before:start', function(App, options) {
    var Application = App;
    Application.save = Module.saveAndProvidePromise;
    Application.getChannel().on('autoSave', Module.autoSave);

    window.onbeforeunload = Module.beforeExitWithUnsavedChanges;

    Application.getChannel().on('save', function(saveResult) { Application.save(saveResult); });
  });

  App.on('start', function(App, options) {
    var saveView = new Module.SaveView();
    App._appView.showChildView('bottomRegion', saveView);
  });

  return Module;
});
