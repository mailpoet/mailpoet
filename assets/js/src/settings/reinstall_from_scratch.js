define(
  [
    'mailpoet'
  ],
  function ( // eslint-disable-line func-names
    MailPoet
  ) {
    var element;
    function eventHandler() {
      if (confirm(MailPoet.I18n.t('reinstallConfirmation'))) {
        MailPoet.trackEvent(
          'User has reinstalled MailPoet via Settings',
          { 'MailPoet Free version': window.mailpoet_version }
        );

        MailPoet.Modal.loading(true);
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'setup',
          action: 'reset'
        }).always(function () { // eslint-disable-line func-names
          MailPoet.Modal.loading(false);
        }).done(function () { // eslint-disable-line func-names
          window.location = 'admin.php?page=mailpoet-newsletters';
        }).fail(function (response) { // eslint-disable-line func-names
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(function (error) { // eslint-disable-line func-names
                return error.message;
              }),
              { scroll: true }
            );
          }
        });
      }
      return false;
    }

    element = document.getElementById('mailpoet_reinstall');
    if (element) {
      element.addEventListener('click', eventHandler, false);
    }
  });
