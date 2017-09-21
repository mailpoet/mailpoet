define([
  'mailpoet',
  'jquery',
  'parsleyjs'
],
function (
  MailPoet,
  jQuery,
  Parsley
) {
  jQuery(function ($) {
    function isSameDomain(url) {
      var link = document.createElement('a');
      link.href = url;
      return (window.location.hostname === link.hostname);
    }

    $(function () {
      // setup form validation
      $('form.mailpoet_form').each(function () {
        var form = $(this);

        form.parsley().on('form:validated', function (parsley) {
          // clear messages
          form.find('.mailpoet_message > p').hide();

          // resize iframe
          if(window.frameElement !== null) {
            MailPoet.Iframe.autoSize(window.frameElement);
          }
        });

        form.parsley().on('form:submit', function (parsley) {
          var form_data = form.serializeObject() || {};
          // check if we're on the same domain
          if(isSameDomain(window.MailPoetForm.ajax_url) === false) {
            // non ajax post request
            return true;
          } else {
            // ajax request
            MailPoet.Ajax.post({
              url: window.MailPoetForm.ajax_url,
              token: form_data.token,
              api_version: form_data.api_version,
              endpoint: 'subscribers',
              action: 'subscribe',
              data: form_data.data
            }).fail(function (response) {
              form.find('.mailpoet_validate_error').html(
                response.errors.map(function (error) {
                  return error.message;
                }).join('<br />')
              ).show();
            }).done(function (response) {
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

              // resize iframe
              if (
                window.frameElement !== null
                && MailPoet !== undefined
                && MailPoet['Iframe']
              ) {
                MailPoet.Iframe.autoSize(window.frameElement);
              }
            });
          }
          return false;
        });
      });
    });
  });
});