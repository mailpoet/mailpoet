define('public', ['jquery', 'jquery-validation'],
  function($) {

    function isSameDomain(url) {
      var link = document.createElement('a');
      link.href = url;
      return (window.location.hostname === link.hostname);
    }

    $(function() {
      // setup form validation
      $('form.mailpoet_form').validate({
        submitHandler: function(form) {
          console.log(form);
          $(form).ajaxSubmit({
            target: "#result"
          });
        }
      });
    });
  }
);
