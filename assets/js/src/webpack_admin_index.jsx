// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

import 'subscribers/subscribers.jsx'; // side effect - renders ReactDOM to document
import 'newsletters/newsletters.jsx'; // side effect - renders ReactDOM to window
import 'segments/segments.jsx'; // side effect - renders ReactDOM to document
import 'settings/settings.jsx'; // side effect - renders ReactDOM to document
import 'forms/forms.jsx'; // side effect - renders ReactDOM to document
import 'settings/tabs.js'; // side effect - assigns to MailPoet.Router, executes code on doc ready
import 'help/help.jsx'; // side effect - renders ReactDOM to document
import 'intro.jsx'; // side effect - assigns to MailPoet.showIntro
import 'poll.jsx'; // side effect - assigns to MailPoet.Poll
import 'settings/reinstall_from_scratch.js'; // side effect - adds event handler to document
import 'subscribers/importExport/import.jsx'; // side effect - executes on doc ready, adds events
import 'subscribers/importExport/export.js'; // side effect - executes on doc ready
import 'wizard/wizard.jsx'; // side effect - renders ReactDOM to document
import 'experimental_features/experimental_features.jsx'; // side effect - renders ReactDOM to document
