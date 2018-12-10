define('mailpoet', [], function mailpoet() {
  // A placeholder for MailPoet object
  var MailPoet = {};

  // Expose MailPoet globally
  window.MailPoet = MailPoet;

  return MailPoet;
});

require('ajax'); // side effect - extends MailPoet object
require('i18n'); // side effect - extends MailPoet object
require('iframe'); // side effect - extends MailPoet object
