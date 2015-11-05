define('public', ['mailpoet', 'jquery', 'jquery-validation'],
  function(MailPoet, $) {
    'use strict';

    function isSameDomain(url) {
      var link = document.createElement('a');
      link.href = url;
      return (window.location.hostname === link.hostname);
    }

    $(function() {
      // setup form validation
      $('form.mailpoet_form').each(function() {
        $(this).validate({
          submitHandler: function(form) {
            var data = $(form).serializeObject() || {};

            // clear messages
            $(form).find('.mailpoet_message').html('');

            // check if we're on the same domain
            if(isSameDomain(MailPoetForm.ajax_url) === false) {
              // non ajax post request
              return true;
            } else {
              // ajax request
              MailPoet.Ajax.post({
                url: MailPoetForm.ajax_url,
                token: MailPoetForm.token,
                endpoint: 'subscribers',
                action: 'subscribe',
                data: data,
                onSuccess: function(response) {
                  if(response.result !== true) {
                    // errors
                    $.each(response.errors, function(index, error) {
                      $(form)
                        .find('.mailpoet_message')
                        .append('<p class="mailpoet_validate_error">'+
                          error+
                        '</p>');
                    });
                  } else {
                    // successfully subscribed
                    if(response.page !== undefined) {
                      // go to page
                      window.location.href = response.page;
                    } else if(response.message !== undefined) {
                      // display success message
                      $(form)
                        .find('.mailpoet_message')
                        .html('<p class="mailpoet_validate_success">'+
                          response.message+
                        '</p>');
                    }

                    // reset form
                    $(form).trigger('reset');
                  }
                }
              });
            }
            return false;
          }
        });
      });
    });
  }
);
