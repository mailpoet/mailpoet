// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

import 'mailpoet'; // side effect - assigns MailPoet to window
import 'dismissible-notice.jsx'; // side effect - adds jQuery event
import 'jquery.serialize-object'; // side effect - extends jQuery
import 'parsleyjs'; // side effect - extends jQuery
import 'parsley-validators.jsx';
