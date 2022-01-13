// A placeholder for MailPoet object
var MailPoet = {};

// Expose MailPoet globally
window.MailPoet = MailPoet;

export default MailPoet;

require('ajax'); // side effect - extends MailPoet object
require('i18n'); // side effect - extends MailPoet object
require('iframe'); // side effect - extends MailPoet object
