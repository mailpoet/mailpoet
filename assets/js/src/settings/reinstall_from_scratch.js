define(
  [
    'mailpoet'
  ],
  function(
    MailPoet
  ) {

    if (MailPoet.I18n.t('reinstallConfirmation') === 'TRANSLATION "reinstallConfirmation" NOT FOUND') {
      return;
    }

    function eventHandler() {
      if (confirm(MailPoet.I18n.t('reinstallConfirmation'))) {
        MailPoet.trackEvent(
          'User has reinstalled MailPoet via Settings',
          {'MailPoet Free version': window.mailpoet_version}
        );

        MailPoet.Modal.loading(true);
        MailPoet.Ajax.post({
          'api_version': window.mailpoet_api_version,
          'endpoint': 'setup',
          'action': 'reset'
        }).always(function () {
          MailPoet.Modal.loading(false);
        }).done(function () {
          window.location = 'admin.php?page=mailpoet-newsletters';
        }).fail(function (response) {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(function (error) {
                return error.message;
              }),
              {scroll: true}
            );
          }
        });
      }
      return false;
    }

    var element = document.getElementById('mailpoet_reinstall');
    if (element) {
      element.addEventListener('click', eventHandler, false);
    }
  });