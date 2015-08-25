define('newsletter_editor/components/save', [
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
  ], function(EditorApplication, Backbone, Marionette) {

  EditorApplication.module("components.save", function(Module, App, Backbone, Marionette, $, _) {
      "use strict";
      var saveTimeout;

      // Save editor contents to server
      Module.save = function() {
          App.getChannel().trigger('beforeEditorSave');

          var json = App.toJSON();


          // save newsletter
          console.log('save disabled');
          // TODO: Migrate logic to new AJAX format
          //mailpoet_post_wpi('newsletter_save.php', json, function(response) {
              //if(response.success !== undefined && response.success === true) {
                  ////MailPoet.Notice.success("<?php _e('Newsletter has been saved.'); ?>");
              //} else if(response.error !== undefined) {
                  //if(response.error.length === 0) {
                      //// TODO: Handle translations
                      //MailPoet.Notice.error("<?php _e('An unknown error occurred, please check your settings.'); ?>");
                  //} else {
                      //$(response.error).each(function(i, error) {
                          //MailPoet.Notice.error(error);
                      //});
                  //}
              //}
              //App.getChannel().trigger('afterEditorSave', json, response);
          //}, function(error) {
              //// TODO: Handle saving errors
              //App.getChannel().trigger('afterEditorSave', {}, error);
          //});
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
              'click .mailpoet_save_export': 'exportTemplate',
          },
          initialize: function(options) {
              App.getChannel().on('beforeEditorSave', this.beforeSave, this);
              App.getChannel().on('afterEditorSave', this.afterSave, this);

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
              this.$('.mailpoet_autosaved_at').text(response.time);
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

              this.hideOptionContents();
          },
          exportTemplate: function() {
              console.log('Exporting template');
              this.hideOptionContents();
          },
          hideOptionContents: function() {
              this.hideSaveAsTemplate();
              this.$('.mailpoet_save_options').addClass('mailpoet_hidden');
          },
          next: function() {
              this.hideOptionContents();
              console.log('Next');
              window.location.href = App.getConfig().get('urls.send');
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
  });

});
