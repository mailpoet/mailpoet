/* eslint-disable func-names */
import { App } from 'newsletter_editor/App';
import { CommunicationComponent } from 'newsletter_editor/components/communication';
import { MailPoet } from 'mailpoet';
import Marionette from 'backbone.marionette';
import $ from 'jquery';
import Blob from 'blob';
import FileSaver from 'file-saver';
import { isTruthy, fromNewsletter } from 'common';
import _ from 'underscore';
import { __, _x } from '@wordpress/i18n';
import SuperModel from 'backbone.supermodel/build/backbone.supermodel';

var Module = {};
var saveTimeout;
var skipNextAutoSave;

Module.isConfirmationEmailValid = function () {
  var json = App.toJSON();
  var body =
    json && json.body && json.body.content
      ? JSON.stringify(json.body.content)
      : '';

  if (
    App.getConfig().get('validation.validateActivationLinkIsPresent') &&
    body.indexOf('[activation_link]') < 0
  ) {
    $('.mailpoet_save_error')
      .html(
        __(
          "Don't forget to include the [activation_link] shortcode in the email",
          'mailpoet',
        ),
      )
      .removeClass('mailpoet_hidden');

    $('.mailpoet_save_button')
      .attr('disabled', 'disabled')
      .addClass('button-disabled');
    $('.mailpoet_editor_last_saved .mailpoet_autosaved_message').addClass(
      'mailpoet_hidden',
    ); // remove the Autosaved text

    return false;
  }

  return true;
};

// Save editor contents to server
Module.save = function () {
  var json = App.toJSON();
  var editorTop = $('#mailpoet_editor_top');
  var deferredFunc = $.Deferred();

  // Stringify to enable transmission of primitive non-string value types
  if (!_.isUndefined(json.body)) {
    if (json.body.blockDefaults) {
      delete json.body.blockDefaults.woocommerceHeading;
      delete json.body.blockDefaults.woocommerceContent;
      if (json.body.blockDefaults && json.body.blockDefaults.coupon) {
        delete json.body.blockDefaults.coupon?.couponId;
        delete json.body.blockDefaults.coupon?.code;
      }
    }
    json.body = JSON.stringify(json.body);
  }

  if (!Module.isConfirmationEmailValid()) {
    return deferredFunc.resolve(); // continue the chain
  }

  App.getChannel().trigger('beforeEditorSave', json);

  // save newsletter
  return CommunicationComponent.saveNewsletter(json)
    .done(function (response) {
      if (response.success !== undefined && response.success === true) {
        // TODO: Handle translations
        // MailPoet.Notice.success("<?php _e('Newsletter has been saved.'); ?>");
      } else if (response.error !== undefined) {
        if (response.error.length === 0) {
          MailPoet.Notice.error(
            __('Template has not been saved, please try again', 'mailpoet'),
            {
              positionAfter: editorTop,
              scroll: true,
            },
          );
        } else {
          $(response.error).each(function (i, error) {
            MailPoet.Notice.error(error, {
              positionAfter: editorTop,
              scroll: true,
            });
          });
        }
      }
      if (!_.isUndefined(json.body)) {
        json.body = JSON.parse(json.body);
      }
      App.getChannel().trigger('afterEditorSave', json, response);
    })
    .fail(function (response) {
      App.getChannel().trigger('editorSaveFailed', {}, response);
    });
};

Module.saveTemplate = function (options) {
  return fromNewsletter(App.toJSON()).then(function (thumbnail) {
    var data = _.extend(options || {}, {
      thumbnail_data: thumbnail,
      body: JSON.stringify(App.getBody()),
      categories: JSON.stringify(['saved', App.getNewsletter().get('type')]),
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
  return fromNewsletter(App.toJSON()).then(function (thumbnail) {
    var data = _.extend(options || {}, {
      thumbnail_data: thumbnail,
      body: App.getBody(),
      categories: JSON.stringify(['saved', App.getNewsletter().get('type')]),
    });
    var blob = new Blob([JSON.stringify(data)], {
      type: 'application/json;charset=utf-8',
    });

    FileSaver.saveAs(blob, 'template.json');
    MailPoet.trackEvent('Editor > Template exported');
  });
};

Module.SaveView = Marionette.View.extend({
  getTemplate: function () {
    return window.templates.save;
  },
  templateContext: function () {
    return {
      wrapperClass: this.wrapperClass,
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
      isAutomationEmail: this.model.isAutomationEmail(),
      woocommerceCustomizerEnabled: App.getConfig().get(
        'woocommerceCustomizerEnabled',
      ),
      isConfirmationEmailTemplate: this.model.isConfirmationEmailTemplate(),
      confirmationEmailCustomizerEnabled: App.getConfig().get(
        'confirmationEmailCustomizerEnabled',
      ),
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
    'click .mailpoet_save_activate_wc_customizer_button':
      'activateWooCommerceCustomizer',
    /* Confirmation email */
    'click .mailpoet_save_activate_confirmation_email_customizer_button':
      'activateConfirmationEmailCustomizer',
    /* Automation email */
    'click .mailpoet_save_go_to_automation': 'saveAndGoToAutomation',
    'click .mailpoet_show_preview': 'showPreview',
  },

  initialize: function () {
    this.setDropdownDirectionDown();
    App.getChannel().on('beforeEditorSave', this.beforeSave, this);
    App.getChannel().on('afterEditorSave', this.afterSave, this);
    App.getChannel().on('editorSaveFailed', this.handleSavingErrors, this);
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
    if (this.model.isConfirmationEmailTemplate()) {
      if (!this.$('.mailpoet_save_button').hasClass('button-disabled')) {
        this.hideSaveOptions();
        App.getChannel().request('save');
      }
    } else {
      this.hideSaveOptions();
      App.getChannel().request('save');
    }
  },
  beforeSave: function () {
    // TODO: Add a loading animation instead
    this.$('.mailpoet_autosaved_at').text(__('Saving...', 'mailpoet'));
  },
  afterSave: function (json) {
    this.validateNewsletter(json);
    // Update 'Last saved timer'
    this.$(
      '.mailpoet_editor_last_saved .mailpoet_autosaved_message',
    ).removeClass('mailpoet_hidden');
    this.$('.mailpoet_autosaved_at').text('');
  },
  handleSavingErrors: function () {
    this.showError(
      __(
        'The email could not be saved. Please, clear browser cache and reload the page. If the problem persists, duplicate the email and try again.',
        'mailpoet',
      ),
    );
  },
  showSaveOptions: function () {
    this.$('.mailpoet_save_show_options').addClass(
      'mailpoet_save_show_options_active',
    );
    this.$('.mailpoet_save_options').removeClass('mailpoet_hidden');
    this.hideSaveAsTemplate();
    this.hideExportTemplate();
  },
  hideSaveOptions: function () {
    this.$('.mailpoet_save_show_options').removeClass(
      'mailpoet_save_show_options_active',
    );
    this.$('.mailpoet_save_options').addClass('mailpoet_hidden');
    this.hideSaveAsTemplate();
    this.hideExportTemplate();
  },
  toggleSaveOptions: function () {
    if (
      this.$('.mailpoet_save_show_options').hasClass(
        'mailpoet_save_show_options_active',
      )
    ) {
      this.hideSaveOptions();
    } else {
      this.showSaveOptions();
    }
  },
  showSaveAsTemplate: function () {
    this.$('.mailpoet_save_as_template_container').removeClass(
      'mailpoet_hidden',
    );
  },
  hideSaveAsTemplate: function () {
    this.$('.mailpoet_save_as_template_container').addClass('mailpoet_hidden');
  },
  saveAsTemplate: function () {
    var templateName = this.$('.mailpoet_save_as_template_name').val();
    var editorTop = $('#mailpoet_editor_top');

    if (templateName === '') {
      MailPoet.Notice.error(__('Please add a template name', 'mailpoet'), {
        positionAfter: editorTop,
        scroll: true,
      });
    } else {
      Module.saveTemplate({
        name: templateName,
      })
        .then(function () {
          MailPoet.Notice.success(__('Template has been saved.', 'mailpoet'), {
            positionAfter: editorTop,
            scroll: true,
          });
          MailPoet.trackEvent('Editor > Template saved');
        })
        .catch(function () {
          MailPoet.Notice.error(
            __('Template has not been saved, please try again', 'mailpoet'),
            {
              positionAfter: editorTop,
              scroll: true,
            },
          );
        });
      this.hideSaveOptions();
    }
  },
  showExportTemplate: function () {
    this.$('.mailpoet_export_template_container').removeClass(
      'mailpoet_hidden',
    );
  },
  hideExportTemplate: function () {
    this.$('.mailpoet_export_template_container').addClass('mailpoet_hidden');
  },
  exportTemplate: function () {
    var templateName = this.$('.mailpoet_export_template_name').val();
    var editorTop = $('#mailpoet_editor_top');

    if (templateName === '') {
      MailPoet.Notice.error(__('Please add a template name', 'mailpoet'), {
        positionAfter: editorTop,
        scroll: true,
      });
    } else {
      Module.exportTemplate({
        name: templateName,
      });
      this.hideExportTemplate();
    }
  },
  showPreview: function () {
    var json = App.toJSON();

    // Stringify to enable transmission of primitive non-string value types
    if (!_.isUndefined(json.body)) {
      json.body = JSON.stringify(json.body);
    }

    MailPoet.Modal.loading(true);

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'showPreview',
      data: json,
    })
      .always(function () {
        MailPoet.Modal.loading(false);
      })
      .done(
        function (response) {
          this.previewView = new Module.NewsletterPreviewView({
            model: new Module.NewsletterPreviewModel(),
            previewType: window.localStorage.getItem(
              App.getConfig().get(
                'newsletterPreview.previewTypeLocalStorageKey',
              ),
            ),
            previewUrl: response.meta.preview_url,
          });

          this.previewView.render();

          MailPoet.Modal.popup({
            template: '',
            element: this.previewView.$el,
            minWidth: '95%',
            height: '100%',
            title: __('Newsletter Preview', 'mailpoet'),
            onCancel: function () {
              this.previewView.destroy();
              this.previewView = null;
            }.bind(this),
          });

          MailPoet.trackEvent('Editor > Browser Preview');
        }.bind(this),
      )
      .fail(function (response) {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(function (error) {
              return error.message;
            }),
            { scroll: true },
          );
        }
      });
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
  saveAndGoToAutomation: function () {
    this.hideSaveOptions();
    Module._cancelAutosave();
    Module.save().done(function () {
      const newsletter = App.getNewsletter();
      const automationId = newsletter.get('options').get('automationId');
      const goToUrl = `admin.php?page=mailpoet-automation-editor&id=${automationId}`;
      window.location.href = goToUrl;
    });
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
      if (
        !content.blocks ||
        !Array.isArray(content.blocks) ||
        content.blocks.length === 0
      ) {
        this.showValidationError(
          __(
            'Poet, please add prose to your masterpiece before you send it to your followers.',
            'mailpoet',
          ),
        );
        return;
      }
    } else {
      // Newsletter body object is missing. This logic shouldn't normally be triggered because
      // validation should not be invoked if saving errors occur, but in case it does happen,
      // this message is better than the validation-specific ones like "no unsubscribe link".
      this.handleSavingErrors();
      return;
    }

    if (
      App.getConfig().get('validation.validateUnsubscribeLinkPresent') &&
      body.indexOf('[link:subscription_unsubscribe_url]') < 0 &&
      body.indexOf('[link:subscription_unsubscribe]') < 0 &&
      newsletter.get('status') !== 'sent'
    ) {
      this.showValidationError(
        __(
          'All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.',
          'mailpoet',
        ),
      );
      return;
    }

    if (
      App.getConfig().get('validation.validateActivationLinkIsPresent') &&
      body.indexOf('[activation_link]') < 0
    ) {
      this.showValidationError(
        __(
          "Don't forget to include the [activation_link] shortcode in the email",
          'mailpoet',
        ),
      );
      return;
    }

    if (
      newsletter.get('type') === 're_engagement' &&
      body.indexOf('[link:subscription_re_engage_url]') < 0
    ) {
      this.showValidationError(
        __(
          'A re-engagement email must include a link with [link:subscription_re_engage_url] shortcode.',
          'mailpoet',
        ),
      );
      return;
    }

    if (
      newsletter.get('type') === 'notification' &&
      body.indexOf('"type":"automatedLatestContent"') < 0 &&
      body.indexOf('"type":"automatedLatestContentLayout"') < 0
    ) {
      this.showValidationError(
        _x(
          'Please add an “Automatic Latest Content” widget to the email from the right sidebar.',
          '(Please reuse the current translation used for the string “Automatic Latest Content”) This Error message is displayed when a user tries to send a “Post Notification” email without any “Automatic Latest Content” widget inside',
          'mailpoet',
        ),
      );
      return;
    }

    if (
      newsletter.get('type') === 'standard' &&
      newsletter.get('status') === 'sent'
    ) {
      this.showValidationError(
        __(
          'This email has already been sent. It can be edited, but not sent again. Duplicate this email if you want to send it again.',
          'mailpoet',
        ),
      );
      return;
    }

    this.hideValidationError();
  },
  showError: function (message) {
    var $el = this.$('.mailpoet_save_error');
    $el.html(message.replace(/\. /g, '.<br>'));
    $el.removeClass('mailpoet_hidden');
  },
  hideError: function () {
    this.$('.mailpoet_save_error').addClass('mailpoet_hidden');
  },
  showValidationError: function (message) {
    this.showError(message);
    this.$('.mailpoet_save_next').addClass('button-disabled');

    if (this.model.isConfirmationEmailTemplate()) {
      this.$('.mailpoet_save_button')
        .attr('disabled', 'disabled')
        .addClass('button-disabled');
    }
  },
  hideValidationError: function () {
    this.hideError();
    this.$('.mailpoet_save_next').removeClass('button-disabled');
    if (this.model.isConfirmationEmailTemplate()) {
      this.$('.mailpoet_save_button')
        .removeAttr('disabled')
        .removeClass('button-disabled');
    }
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
    })
      .done(function () {
        $el.addClass('mailpoet_hidden');
        MailPoet.trackEvent('Editor > WooCommerce email customizer enabled');
      })
      .fail(function (response) {
        MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
      });
  },
  activateConfirmationEmailCustomizer: function () {
    var $el = $('.mailpoet_save_confirmation_email_disabled');
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data: {
        'signup_confirmation.use_mailpoet_editor': 1,
      },
    })
      .done(function () {
        $el.addClass('mailpoet_hidden');
        MailPoet.trackEvent('Editor > Confirmation email customizer enabled');
      })
      .fail(function (response) {
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
    if (!Module.isConfirmationEmailValid()) {
      Module._cancelAutosave();
      return;
    }
    App.getChannel()
      .request('save')
      .always(function () {
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
    message = __(
      'There are unsaved changes which will be lost if you leave this page.',
      'mailpoet',
    );
    event = e || window.event;

    if (event) {
      event.returnValue = message;
    }

    return message;
  }
  return undefined;
};

Module.NewsletterPreviewModel = SuperModel.extend({
  defaults: {
    previewSendingError: false,
    previewSendingSuccess: false,
    sendingPreview: false,
    mssPendingApproval: window.mailpoet_mss_key_pending_approval,
    mssKeyPendingApprovalRefreshMessage: true,
    awaitingKeyCheck: false,
  },
});

Module.NewsletterPreviewView = Marionette.View.extend({
  className: 'mailpoet_browser_preview_wrapper',
  getTemplate: function () {
    return window.templates.newsletterPreview;
  },
  modelEvents: {
    change: 'render',
  },
  events: function () {
    return {
      'change .mailpoet_browser_preview_type': 'changeBrowserPreviewType',
      'click #mailpoet_send_preview': 'sendPreview',
      'click #refresh-mss-key-status': 'refreshMssKeyStatus',
    };
  },
  initialize: function (options) {
    this.previewType = options.previewType || 'mobile';
    this.previewUrl = options.previewUrl;
    this.width = '100%';
    this.height = '100%';
  },
  templateContext: function () {
    return {
      previewType: this.previewType,
      previewUrl: this.previewUrl,
      width: this.width,
      height: this.height,
      email:
        this.$('#mailpoet_preview_to_email').val() || window.currentUserEmail,
      previewSendingError: this.model.get('previewSendingError'),
      sendingPreview: this.model.get('sendingPreview'),
      mssKeyPendingApproval: this.model.get('mssPendingApproval'),
      mssKeyPendingApprovalRefreshMessage: this.model.get(
        'mssKeyPendingApprovalRefreshMessage',
      ),
      awaitingKeyCheck: this.model.get('awaitingKeyCheck'),
    };
  },
  changeBrowserPreviewType: function (event) {
    var value = $(event.target).val();

    if (value === 'mobile') {
      this.$('.mailpoet_browser_preview_container').addClass(
        'mailpoet_browser_preview_container_mobile',
      );
      this.$('.mailpoet_browser_preview_container').removeClass(
        'mailpoet_browser_preview_container_desktop',
      );
      this.$('.mailpoet_browser_preview_container').removeClass(
        'mailpoet_browser_preview_container_send_to_email',
      );
    } else if (value === 'desktop') {
      this.$('.mailpoet_browser_preview_container').addClass(
        'mailpoet_browser_preview_container_desktop',
      );
      this.$('.mailpoet_browser_preview_container').removeClass(
        'mailpoet_browser_preview_container_mobile',
      );
      this.$('.mailpoet_browser_preview_container').removeClass(
        'mailpoet_browser_preview_container_send_to_email',
      );
    } else {
      this.$('.mailpoet_browser_preview_container').addClass(
        'mailpoet_browser_preview_container_send_to_email',
      );
      this.$('.mailpoet_browser_preview_container').removeClass(
        'mailpoet_browser_preview_container_desktop',
      );
      this.$('.mailpoet_browser_preview_container').removeClass(
        'mailpoet_browser_preview_container_mobile',
      );
    }

    window.localStorage.setItem(
      App.getConfig().get('newsletterPreview.previewTypeLocalStorageKey'),
      value,
    );
    this.previewType = value;
  },
  sendPreview: function () {
    // get form data
    var that = this;
    var $emailField = this.$('#mailpoet_preview_to_email');
    var data = {
      subscriber: $emailField.val(),
      id: App.getNewsletter().get('id'),
    };

    if (data.subscriber.length <= 0) {
      MailPoet.Notice.error(
        __(
          'Enter an email address to send the preview newsletter to.',
          'mailpoet',
        ),
        {
          positionAfter: $emailField,
          scroll: true,
        },
      );
      return false;
    }

    this.model.set('previewSendingError', false);
    this.model.set('previewSendingSuccess', false);
    this.model.set('sendingPreview', true);
    // save before sending
    App.getChannel()
      .request('save')
      .always(function () {
        CommunicationComponent.previewNewsletter(data)
          .done(function () {
            that.model.set('sendingPreview', false);
            that.model.set('previewSendingSuccess', true);
            MailPoet.trackEvent('Editor > Preview sent', {
              'Domain name': data.subscriber.substring(
                data.subscriber.indexOf('@') + 1,
              ),
            });
          })
          .fail(function (response) {
            that.model.set('sendingPreview', false);
            that.model.set('previewSendingError', true);
            let errorHtml = `<p>${__(
              'Sorry, there was an error, please try again later.',
              'mailpoet',
            )}</p>`;
            if (response.errors.length > 0) {
              const errors = response.errors.map(function (error) {
                let errorMessage = `
              <p>
                ${__(
                  'The email could not be sent due to a technical issue with %1$s',
                  'mailpoet',
                ).replace('%1$s', window.config.mtaMethod)}:
                <i>${error.message}</i>
              </p>
            `;
                if (window.config.mtaMethod === 'PHPMail') {
                  errorMessage += `
                <p>${__(
                  'Please check your sending method configuration, you may need to consult with your hosting company.',
                  'mailpoet',
                )}</p>
                <br />
                <p>${__(
                  'The easy alternative is to <b>send emails with MailPoet Sending Service</b> instead, like thousands of other users do.',
                  'mailpoet',
                )}</p>
                <p>
                  <a
                    href='${MailPoet.MailPoetComUrlFactory.getFreePlanUrl({
                      utm_campaign: 'sending-error',
                    })}'
                    target='_blank'
                    rel='noopener noreferrer'
                  >
                    ${__('Sign up for free in minutes', 'mailpoet')}
                  </a>
                </p>
              `;
                } else {
                  const checkSettingsNotice = __(
                    'Check your [link]sending method settings[/link].',
                    'mailpoet',
                  ).replace(
                    /\[link\](.*?)\[\/link\]/g,
                    '<a href="?page=mailpoet-settings#mta" key="check-sending">$1</a>',
                  );
                  errorMessage += `<p>${checkSettingsNotice}</p>`;
                }
                return errorMessage;
              });
              errorHtml = errors.join('');
            }
            document.getElementById(
              'mailpoet_preview_sending_error',
            ).innerHTML = errorHtml;
          });
      });
    return undefined;
  },
  refreshMssKeyStatus: function () {
    this.model.set('awaitingKeyCheck', true);

    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'services',
      action: 'refreshMSSKeyStatus',
    })
      .done((response) => {
        this.model.set('awaitingKeyCheck', false);
        if (response.data && response.data.result.code === 200) {
          this.model.set(
            'mssPendingApproval',
            !isTruthy(response.data.result.data.is_approved),
          );
          this.model.set('mssKeyPendingApprovalRefreshMessage', false);
        }
      })
      .fail((response) => {
        this.model.set('awaitingKeyCheck', false);
        if (response.errors && Array.isArray(response.errors)) {
          const messages = response.errors.map((e) => e.message);
          const errorEl = document.querySelector('.pendindig_approval_error');
          errorEl.innerHTML = messages.join('\n');
        }
      });
  },
});

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

export { Module as SaveComponent };
