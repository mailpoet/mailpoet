define([], function mailpoet() {
  // A placeholder for MailPoet object
  var MailPoet = {};

  // Expose MailPoet globally
  window.MailPoet = MailPoet;

  return MailPoet;
});

require('ajax'); // side effect - extends MailPoet object
require('date'); // side effect - extends MailPoet object
require('i18n'); // side effect - extends MailPoet object
require('modal'); // side effect - extends MailPoet object
require('notice'); // side effect - extends MailPoet object
require('num'); // side effect - extends MailPoet object
require('analytics_event'); // side effect - extends MailPoet object
require('help-tooltip'); // side effect - extends MailPoet object
