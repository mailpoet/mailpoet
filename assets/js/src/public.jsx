import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import Cookies from 'js-cookie';
import 'parsleyjs';

const exitIntentEvent = 'mouseleave.mailpoet.form-exit-intent';

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

  /**
   * @param form jQuery object of form form.mailpoet_form
   */
  function checkFormContainer(form) {
    if (form.width() < 400) {
      form.addClass('mailpoet_form_tight_container');
    } else {
      form.removeClass('mailpoet_form_tight_container');
    }
  }

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

  function renderFontFamily(fontName, formDiv) {
    const originalFontFamily = formDiv.css('font-family');
    const newFontFamily = `"${fontName}", ${originalFontFamily}`;
    formDiv.css('font-family', newFontFamily);
    formDiv.find('input, option').css('font-family', 'inherit');
    formDiv.find('input[type=text], textarea, input[type=email], select').css('font-family', newFontFamily);
    formDiv.find(':header').css('font-family', 'inherit');

    formDiv.find('input[data-font-family]').each(function applyFontFamilyToInput() {
      const element = $(this);
      const inputFontFamily = element.data('font-family');
      const inputOriginalFontFamily = element.css('font-family');
      const inputNewFontFamily = `"${inputFontFamily}", ${inputOriginalFontFamily}`;
      element.css('font-family', inputNewFontFamily);
    });

    formDiv.find('.mailpoet-has-font').each(function applyFontFamilyToRichText() {
      const element = $(this);
      const spanOriginalFontFamily = element.css('font-family');
      const spanNewFontFamily = `"${spanOriginalFontFamily}", ${originalFontFamily}`;
      element.css('font-family', spanNewFontFamily);
    });
  }

  function doDisplayForm(formDiv, showOverlay) {
    formDiv.addClass('active');
    checkFormContainer(formDiv);

    if (showOverlay) {
      formDiv.prev('.mailpoet_form_popup_overlay').addClass('active');
    }
  }

  function showForm(formDiv, showOverlay = false) {
    const form = formDiv.find('form');
    const position = form.data('position');
    formDiv.addClass(`mailpoet_form_position_${position}`);
    const animation = form.data('animation');
    if (animation !== '') {
      formDiv.addClass(`mailpoet_form_animation_${animation}`);
      formDiv.prev('.mailpoet_form_popup_overlay').addClass(`mailpoet_form_overlay_animation_${animation}`);
      formDiv.prev('.mailpoet_form_popup_overlay').addClass(`mailpoet_form_overlay_animation`);
    }
    const background = form.data('background-color');
    formDiv.css('background-color', background || 'white');
    let delay = form.data('delay');
    delay = parseInt(delay, 10);
    if (Number.isNaN(delay)) {
      delay = 0;
    }
    const timeout = setTimeout(() => {
      $(document).off(exitIntentEvent);
      doDisplayForm(formDiv, showOverlay);
    }, delay * 1000);

    const exitIntentEnabled = form.data('exit-intent-enabled');
    if (exitIntentEnabled) {
      $(document).on(exitIntentEvent, () => {
        $(document).off(exitIntentEvent);
        clearTimeout(timeout);
        doDisplayForm(formDiv, showOverlay);
      });
    }
  }

  const closeForm = (formDiv) => {
    formDiv.removeClass('active');
    formDiv.prev('.mailpoet_form_popup_overlay').removeClass('active');
    Cookies.set('popup_form_dismissed', '1', { expires: 365, path: '/' });
  };

  $(document).keyup((e) => {
    if (e.key === 'Escape') {
      $('div.mailpoet_form').each((index, element) => {
        if ($(element).children('.mailpoet_form_close_icon').length !== 0) {
          closeForm($(element));
        }
      });
    }
  });

  $(() => {
    $('.mailpoet_form').each((index, element) => {
      $(element).children('.mailpoet_paragraph, .mailpoet_form_image, .mailpoet_form_paragraph').last().addClass('last');
    });
    $('form.mailpoet_form').each((index, element) => {
      const form = $(element);
      if (form.data('font-family')) {
        renderFontFamily(form.data('font-family'), form.parent());
      }
    });
    $('.mailpoet_form_close_icon').click((event) => {
      const closeIcon = $(event.target);
      const formDiv = closeIcon.parent();
      if (formDiv.data('is-preview')) return; // Do not close popup in preview
      closeForm(formDiv);
    });

    $('div.mailpoet_form_fixed_bar, div.mailpoet_form_slide_in').each((index, element) => {
      const cookieValue = Cookies.get('popup_form_dismissed');
      const formDiv = $(element);
      if (cookieValue === '1' && !formDiv.data('is-preview')) return;
      showForm(formDiv);
    });

    $('div.mailpoet_form_popup').each((index, element) => {
      const cookieValue = Cookies.get('popup_form_dismissed');
      const formDiv = $(element);
      if (cookieValue === '1' && !formDiv.data('is-preview')) return;
      const showOverlay = true;
      showForm(formDiv, showOverlay);
    });

    $(window).resize(() => {
      $('.mailpoet_form').each((index, element) => {
        // Detect form is placed in tight container
        const formDiv = $(element);
        checkFormContainer(formDiv);
      });
    });

    // setup form validation
    $('form.mailpoet_form').each((index, element) => {
      const form = $(element);
      // Detect form is placed in tight container
      checkFormContainer(form.closest('div.mailpoet_form'));
      form.parsley().on('form:validated', () => {
        // clear messages
        form.find('.mailpoet_message > p').hide();

        // resize iframe
        if (window.frameElement !== null) {
          MailPoet.Iframe.autoSize(window.frameElement);
        }
      });

      form.parsley().on('form:submit', (parsley) => {
        // Disable form submit in preview mode
        const formDiv = form.parent('.mailpoet_form');
        if (formDiv && formDiv.data('is-preview')) {
          return false;
        }
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

    // Manage subscription form
    $('.mailpoet-manage-subscription').on('submit', () => {
      $('.mailpoet-manage-subscription .mailpoet-submit-success').hide();
    });
  });
});
