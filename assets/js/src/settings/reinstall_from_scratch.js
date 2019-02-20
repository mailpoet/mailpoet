import MailPoet from 'mailpoet';

var element;
function eventHandler() {
      if (confirm(MailPoet.I18n.t('reinstallConfirmation'))) { // eslint-disable-line
    MailPoet.trackEvent(
      'User has reinstalled MailPoet via Settings',
      { 'MailPoet Free version': window.mailpoet_version }
    );

    MailPoet.Modal.loading(true);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'setup',
      action: 'reset',
    }).always(function alwaysCb() {
      MailPoet.Modal.loading(false);
    }).done(function doneCb() {
      window.location = 'admin.php?page=mailpoet-newsletters';
    }).fail(function failCb(response) {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(function responseMapCb(error) {
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
