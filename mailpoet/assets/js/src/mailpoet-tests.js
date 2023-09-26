// A placeholder for MailPoet object
var MailPoet = {};

// Expose MailPoet globally
window.MailPoet = MailPoet;

export { MailPoet };

require('i18n'); // side effect - extends MailPoet object
require('notice'); // side effect - extends MailPoet object
require('help-tooltip'); // side effect - extends MailPoet object
