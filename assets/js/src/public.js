import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import 'parsleyjs';

jQuery(function ($) { // eslint-disable-line func-names
  window.reCaptchaCallback = function reCaptchaCallback() {
    $('.mailpoet_recaptcha').each(function () { // eslint-disable-line func-names
      var sitekey = $(this).attr('data-sitekey');
      var container = $(this).find('> .mailpoet_recaptcha_container').get(0);
      var field = $(this).find('> .mailpoet_recaptcha_field');
      var widgetId;
      if (sitekey) {
        widgetId = window.grecaptcha.render(container, { sitekey: sitekey, size: 'compact' });
        field.val(widgetId);
      }
    });
  };

  function isSameDomain(url) {
    var link = document.createElement('a');
    link.href = url;
    return (window.location.hostname === link.hostname);
  }

  function updateCaptcha(e) {
    var captcha;
    var captchaSrc;
    var hashPos;
    var newSrc;
    captcha = $('img.mailpoet_captcha');
    if (!captcha.length) return false;
    captchaSrc = captcha.attr('src');
    hashPos = captchaSrc.indexOf('#');
    newSrc = hashPos > 0 ? captchaSrc.substring(0, hashPos) : captchaSrc;
    captcha.attr('src', newSrc + '#' + new Date().getTime());
    if (e) e.preventDefault();
    return true;
  }

  $(function () { // eslint-disable-line func-names
    // setup form validation
    $('form.mailpoet_form').each(function () { // eslint-disable-line func-names
      var form = $(this);
      // Detect form is placed in tight container
      if (form.width() < 500) {
        form.addClass('mailpoet_form_tight_container');
      }
      form.parsley().on('form:validated', function () { // eslint-disable-line func-names
        // clear messages
        form.find('.mailpoet_message > p').hide();

        // resize iframe
        if (window.frameElement !== null) {
          MailPoet.Iframe.autoSize(window.frameElement);
        }
      });

      form.parsley().on('form:submit', function (parsley) { // eslint-disable-line func-names
        var formData = form.mailpoetSerializeObject() || {};
        // check if we're on the same domain
        if (isSameDomain(window.MailPoetForm.ajax_url) === false) {
          // non ajax post request
          return true;
        }

        if (window.grecaptcha && formData.recaptcha) {
          formData.data.recaptcha = window.grecaptcha.getResponse(formData.recaptcha);
        }

        form.addClass('mailpoet_form_sending');
        // ajax request
        MailPoet.Ajax.post({
          url: window.MailPoetForm.ajax_url,
          token: formData.token,
          api_version: formData.api_version,
          endpoint: 'subscribers',
          action: 'subscribe',
          data: formData.data,
        })
          .fail(function handleFailedPost(response) {
            if (
              response.meta !== undefined
                && response.meta.redirect_url !== undefined
            ) {
              // go to page
              window.top.location.href = response.meta.redirect_url;
            } else {
              if (response.meta && response.meta.refresh_captcha) {
                updateCaptcha();
              }
              form.find('.mailpoet_validate_error').html(
                response.errors.map(function buildErrorMessage(error) {
                  return error.message;
                }).join('<br />')
              ).show();
            }
          })
          .done(function handleRecaptcha(response) {
            if (window.grecaptcha && formData.recaptcha) {
              window.grecaptcha.reset(formData.recaptcha);
            }
            return response;
          })
          .done(function handleSuccess(response) {
            // successfully subscribed
            if (
              response.meta !== undefined
                && response.meta.redirect_url !== undefined
            ) {
              // go to page
              window.location.href = response.meta.redirect_url;
            } else {
              // display success message
              form.find('.mailpoet_validate_success').show();
              // hide elements marked with a class
              form.find('.mailpoet_form_hide_on_success').each(function hideOnSuccess() {
                $(this).hide();
              });
            }

            // reset form
            form.trigger('reset');
            // reset validation
            parsley.reset();
            // reset captcha
            if (window.grecaptcha && formData.recaptcha) {
              window.grecaptcha.reset(formData.recaptcha);
            }

            // resize iframe
            if (
              window.frameElement !== null
                && MailPoet !== undefined
                && MailPoet.Iframe
            ) {
              MailPoet.Iframe.autoSize(window.frameElement);
            }
          })
          .always(function subscribeFormAlways() {
            form.removeClass('mailpoet_form_sending');
          });
        return false;
      });
    });

    $('.mailpoet_captcha_update').on('click', updateCaptcha);
  });
});
