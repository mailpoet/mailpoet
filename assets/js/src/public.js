define([
  'mailpoet',
  'jquery',
  'parsleyjs'
],
function ( // eslint-disable-line func-names
  MailPoet,
  jQuery
) {
  jQuery(function ($) { // eslint-disable-line func-names
    window.reCaptchaCallback = function () {
      $('.mailpoet_recaptcha').each(function () {
        var sitekey = $(this).attr('data-sitekey');
        var container = $(this).find('> .mailpoet_recaptcha_container').get(0);
        var field = $(this).find('> .mailpoet_recaptcha_field');
        var widgetId = window.grecaptcha.render(container, { sitekey: sitekey, size: 'compact' });
        field.val(widgetId);
      });
    };

    function isSameDomain(url) {
      var link = document.createElement('a');
      link.href = url;
      return (window.location.hostname === link.hostname);
    }

    $(function () { // eslint-disable-line func-names
      // setup form validation
      $('form.mailpoet_form').each(function () { // eslint-disable-line func-names
        var form = $(this);

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

          // ajax request
          MailPoet.Ajax.post({
            url: window.MailPoetForm.ajax_url,
            token: formData.token,
            api_version: formData.api_version,
            endpoint: 'subscribers',
            action: 'subscribe',
            data: formData.data
          }).fail(function (response) { // eslint-disable-line func-names
            form.find('.mailpoet_validate_error').html(
                response.errors.map(function (error) { // eslint-disable-line func-names
                  return error.message;
                }).join('<br />')
              ).show();
<<<<<<< HEAD
          }).done(function (response) { // eslint-disable-line func-names
=======
            if (window.grecaptcha) {
              window.grecaptcha.reset(formData.recaptcha);
            }
          }).done(function (response) {
>>>>>>> handling multiple instances of reCaptcha
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
            }

              // reset form
            form.trigger('reset');
              // reset validation
            parsley.reset();
            // reset captcha
            if (window.grecaptcha) {
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
          });

          return false;
        });
      });
    });
  });
});
