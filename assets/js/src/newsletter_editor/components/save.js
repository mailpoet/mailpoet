define([
    'newsletter_editor/App',
    'mailpoet',
    'notice',
    'backbone',
    'backbone.marionette',
    'jquery',
    'blob',
    'filesaver',
    'html2canvas'
  ], function(App, MailPoet, Notice, Backbone, Marionette, jQuery, Blob, FileSaver, html2canvas) {

  "use strict";

  var Module = {},
      saveTimeout;

  // Save editor contents to server
  Module.save = function() {
    App.getChannel().trigger('beforeEditorSave');

    var json = App.toJSON();

    // save newsletter
    MailPoet.Ajax.post({
      endpoint: 'newsletters',
      action: 'save',
      data: json,
    }).done(function(response) {
      if(response.success !== undefined && response.success === true) {
        // TODO: Handle translations
        //MailPoet.Notice.success("<?php _e('Newsletter has been saved.'); ?>");
      } else if(response.error !== undefined) {
        if(response.error.length === 0) {
          // TODO: Handle translations
          MailPoet.Notice.error("<?php _e('An unknown error occurred, please check your settings.'); ?>");
        } else {
          $(response.error).each(function(i, error) {
            MailPoet.Notice.error(error);
          });
        }
      }
      App.getChannel().trigger('afterEditorSave', json, response);
    }).fail(function(response) {
      // TODO: Handle saving errors
      App.getChannel().trigger('afterEditorSave', {}, response);
    });
  };

  Module.saveTemplate = function(options) {
    return MailPoet.Ajax.post({
      endpoint: 'newsletterTemplates',
      action: 'save',
      data: _.extend(options || {}, {
        body: App.getBody(),
      }),
    });
  };

  Module.getThumbnail = function(element, options) {
    return html2canvas(element, options || {});
  };

  Module.exportTemplate = function(options) {
    var that = this;
    return Module.getThumbnail(
      jQuery('#mailpoet_editor_content > .mailpoet_block').get(0)
    ).then(function(thumbnail) {
      var data = _.extend(options || {}, {
        thumbnail: thumbnail.toDataURL(),
        body: App.getBody(),
      });
      var blob = new Blob(
        [JSON.stringify(data)],
        { type: 'application/json;charset=utf-8' }
      );

      FileSaver.saveAs(blob, 'template.json');
    });
  };

  Module.SaveView = Marionette.LayoutView.extend({
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
      'click .mailpoet_export_template': 'exportTemplate',
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
      this.$('.mailpoet_autosaved_at').text('Saving...');
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
          templateDescription = this.$('.mailpoet_save_as_template_description').val();

      console.log('Saving template with ', templateName, templateDescription);
      Module.saveTemplate({
        name: templateName,
        description: templateDescription,
      }).done(function() {
        console.log('Template saved', arguments);
      }).fail(function() {
        // TODO: Handle error messages
        console.log('Template save failed', arguments);
      });

      this.hideOptionContents();
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
          templateDescription = this.$('.mailpoet_export_template_description').val();

      if (templateName === '') {
        MailPoet.Notice.error(App.getConfig().get('translations.templateNameMissing'));
      } else if (templateDescription === '') {
        MailPoet.Notice.error(App.getConfig().get('translations.templateDescriptionMissing'));
      } else {
        console.log('Exporting template with ', templateName, templateDescription);
        Module.exportTemplate({
          name: templateName,
          description: templateDescription,
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
        console.log('Next');
        window.location.href = App.getConfig().get('urls.send');
      }
    },
    validateNewsletter: function(jsonObject) {
      if (!App._contentContainer.isValid()) {
        this.showValidationError(App._contentContainer.validationError);
        return;
      }

      if (App.getConfig().get('validation.validateUnsubscribeLinkPresent') &&
          JSON.stringify(jsonObject).indexOf("[unsubscribeUrl]") < 0) {
        this.showValidationError(App.getConfig().get('translations.unsubscribeLinkMissing'));
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
    },
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
      // TODO: Translate this message
      var message = "There are unsaved changes which will be lost if you leave this page.";
      e = e || window.event;

      if (e) {
        e.returnValue = message;
      }

      return message;
    }
  };

  App.on('before:start', function(options) {
    App.save = Module.save;
    App.getChannel().on('autoSave', Module.autoSave);

    window.onbeforeunload = Module.beforeExitWithUnsavedChanges;

    App.getChannel().on('save', function() { App.save(); });
  });

  App.on('start', function(options) {
    var saveView = new Module.SaveView();
    App._appView.bottomRegion.show(saveView);
  });

  return Module;
});
