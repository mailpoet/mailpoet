import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import 'parsleyjs';

function setCookie(name, value, days) {
  let expires = '';
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = `; expires=${date.toUTCString()}`;
  }
  document.cookie = `${name}=${value}${expires}; path=/`;
}

function getCookie(name) {
  const nameEQ = `${name}=`;
  const cookieParts = document.cookie.split(';');
  for (let i = 0; i < cookieParts.length; i += 1) {
    let cookiePart = cookieParts[i];
    while (cookiePart.charAt(0) === ' ') cookiePart = cookiePart.substring(1, cookiePart.length);
    if (cookiePart.indexOf(nameEQ) === 0) {
      return cookiePart.substring(nameEQ.length, cookiePart.length);
    }
  }
  return null;
}

jQuery(($) => {
  window.reCaptchaCallback = function reCaptchaCallback() {
    $('.mailpoet_recaptcha').each((index, element) => {
      const recaptcha = $(element);
      const sitekey = recaptcha.attr('data-sitekey');
      const container = recaptcha.find('> .mailpoet_recaptcha_container').get(0);
      const field = recaptcha.find('> .mailpoet_recaptcha_field');
      let widgetId;
      if (sitekey) {
        widgetId = window.grecaptcha.render(container, { sitekey, size: 'compact' });
        field.val(widgetId);
      }
    });
  };

  function isSameDomain(url) {
    const link = document.createElement('a');
    link.href = url;
    return (window.location.hostname === link.hostname);
  }

  function updateCaptcha(e) {
    const captcha = $('img.mailpoet_captcha');
    if (!captcha.length) return false;
    const captchaSrc = captcha.attr('src');
    const hashPos = captchaSrc.indexOf('#');
    const newSrc = hashPos > 0 ? captchaSrc.substring(0, hashPos) : captchaSrc;
    captcha.attr('src', `${newSrc}#${new Date().getTime()}`);
    if (e) e.preventDefault();
    return true;
  }

  $(() => {
    const closePopupForm = (formDiv) => {
      formDiv.removeClass('active');
      formDiv.prev('.mailpoet_form_popup_overlay').removeClass('active');
      setCookie('popup_form_dismissed', '1', 365);
    };
    $('.mailpoet_popup_close_icon').click((event) => {
      const closeIcon = $(event.target);
      const formDiv = closeIcon.parent();
      closePopupForm(formDiv);
    });
    $('.mailpoet_form_popup_overlay').click((event) => {
      const overlay = $(event.target);
      const formDiv = overlay.next('div.mailpoet_form_popup');
      closePopupForm(formDiv);
    });

    $('div.mailpoet_form_popup').each((index, element) => {
      const cookieValue = getCookie('popup_form_dismissed');
      if (cookieValue === '1') return;

      const formDiv = $(element);
      const form = formDiv.find('form');
      const background = form.data('background-color');
      formDiv.css('background-color', background || 'white');
      let delay = form.data('delay');
      delay = parseInt(delay, 10);
      if (Number.isNaN(delay)) {
        delay = 0;
      }
      setTimeout(() => {
        formDiv.addClass('active');
        formDiv.prev('.mailpoet_form_popup_overlay').addClass('active');
      }, delay * 1000);
    });

    // setup form validation
    $('form.mailpoet_form').each((index, element) => {
      const form = $(element);
      // Detect form is placed in tight container
      if (form.width() < 500) {
        form.addClass('mailpoet_form_tight_container');
      }
      form.parsley().on('form:validated', () => {
        // clear messages
        form.find('.mailpoet_message > p').hide();

        // resize iframe
        if (window.frameElement !== null) {
          MailPoet.Iframe.autoSize(window.frameElement);
        }
      });

      form.parsley().on('form:submit', (parsley) => {
        const formData = form.mailpoetSerializeObject() || {};
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
          .fail((response) => {
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
                response.errors.map((error) => error.message).join('<br />')
              ).show();
            }
          })
          .done((response) => {
            if (window.grecaptcha && formData.recaptcha) {
              window.grecaptcha.reset(formData.recaptcha);
            }
            return response;
          })
          .done((response) => {
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
          .always(() => {
            form.removeClass('mailpoet_form_sending');
          });
        return false;
      });
    });

    $('.mailpoet_captcha_update').on('click', updateCaptcha);
  });
});
