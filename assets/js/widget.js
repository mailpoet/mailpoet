jQuery(function($) {
  function isSameDomain(url) {
    var link = document.createElement('a');
    link.href = url;
    return (window.location.hostname === link.hostname);
  }

  // backwards compatibility for older jQuery versions
  if($.fn.on === undefined) {
    // mimic on() function using live()
    $.fn.on = function(events, selector, data, handler) {
      if(typeof(selector) === 'function') {
        $(this.context).live(events, selector);
      } else {
        $(selector).live(events, data, handler);
      }
      return this;
    };
  }

  $(function() {
    // setup form validation
    $('form.mailpoet_form').validationEngine('attach', {
      promptPosition: (parseInt(MailPoetWidget.is_rtl, 10) === 1)
                      ? 'centerLeft' : 'centerRight',
      scroll: false,
      onValidationComplete: function(form, is_form_valid) {
        if(is_form_valid === true) {
          // get data
          var raw_data = $(form).serializeArray(),
          data = {};

          // clear messages
          $(form).find('.mailpoet_message').html('');
          // hide all validation messages
          $(form).validationEngine('hideAll');

          // format data
          $.each(raw_data, function(index, value) {
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

          if(isSameDomain(MailPoetWidget.ajax_url)) {
            $.ajax(MailPoetWidget.ajax_url+ '?action=mailpoet_form_subscribe', {
              data: JSON.stringify(data),
              processData: false,
              contentType: "application/json; charset=utf-8",
              type : 'POST',
              dataType: 'json',
              success:function(response) {
                // display response
                if(response.success === true) {
                  if(response.page !== undefined) {
                    window.location.href = response.page;
                  } else if(response.message !== undefined) {
                    // success
                    $(form)
                      .find('.mailpoet_message')
                      .html('<p class="mailpoet_validate_success">'+response.message+'</p>');
                  }

                  $(form).trigger('reset');
                } else {
                  if(response.message !== undefined) {
                    $(form)
                      .find('.mailpoet_message')
                      .html('<p class="mailpoet_validate_error">'+response.message+'</p>');
                  }
                }
              }
            });
          } else {
            return true;
          }
        }
      }
    });
  });
});
