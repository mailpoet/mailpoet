define('public', ['mailpoet', 'jquery', 'jquery-validation'],
  function(MailPoet, $) {
    'use strict';

    function isSameDomain(url) {
      var link = document.createElement('a');
      link.href = url;
      return (window.location.hostname === link.hostname);
    }

    function formatData(raw) {
      var data = {};

      $.each(raw, function(index, value) {
        if(value.name.endsWith('[]')) {
          var value_name = value.name.substr(0, value.name.length - 2);
          // it's an array
          if(data[value_name] === undefined) {
            data[value_name] = [];
          }
            data[value_name].push(value.value);
        } else {
          data[value.name] = value.value;
        }
      });

      return data;
    }

    $(function() {
      // setup form validation
      $('form.mailpoet_form').validate({
        submitHandler: function(form) {
          var data = $(form).serializeArray() || {};

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
              action: 'save',
              data: formatData(data),
              onSuccess: function(response) {
                if(response !== true) {
                  // errors
                  $.each(response, function(index, error) {
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
  }
);
