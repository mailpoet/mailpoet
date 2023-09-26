// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

import 'homepage/homepage'; // side effect - renders ReactDOM to document
import 'subscribers/subscribers.jsx'; // side effect - renders ReactDOM to document
import 'newsletters/newsletters.jsx'; // side effect - renders ReactDOM to window
import 'segments/static/static'; // side effect - renders ReactDOM to document
import 'segments/dynamic/dynamic'; // side effect - renders ReactDOM to document
import 'forms/forms.jsx'; // side effect - renders ReactDOM to document
import 'help/help.jsx'; // side effect - renders ReactDOM to document
import 'subscribers/import-export/import.jsx'; // side effect - executes on doc ready, adds events
import 'subscribers/import-export/export'; // side effect - executes on doc ready
import 'wizard/wizard'; // side effect - renders ReactDOM to document
import 'experimental-features/experimental-features.jsx'; // side effect - renders ReactDOM to document
import 'logs/logs'; // side effect - renders ReactDOM to document
import 'sending-paused-notices-fix-button'; // side effect - renders ReactDOM to document
import 'sending-paused-notices-resume-button'; // side effect - executes on doc ready, adds events
import 'sending-paused-notices-authorize-email'; // side effect - renders ReactDOM to document
import 'landingpage/landingpage'; // side effect - renders ReactDOM to document
import 'wizard/track-wizard-loaded-via-woocommerce';
