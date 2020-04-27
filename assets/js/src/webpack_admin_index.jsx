// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

import 'newsletters/automatic_emails.jsx'; // side effect - sets up automatic emails via hooks
import 'subscribers/subscribers.jsx'; // side effect - renders ReactDOM to document
import 'newsletters/newsletters.jsx'; // side effect - renders ReactDOM to window
import 'segments/segments.jsx'; // side effect - renders ReactDOM to document
import 'forms/forms.jsx'; // side effect - renders ReactDOM to document
import 'help/help.jsx'; // side effect - renders ReactDOM to document
import 'subscribers/importExport/import.jsx'; // side effect - executes on doc ready, adds events
import 'subscribers/importExport/export.js'; // side effect - executes on doc ready
import 'wizard/wizard.jsx'; // side effect - renders ReactDOM to document
import 'experimental_features/experimental_features.jsx'; // side effect - renders ReactDOM to document
import 'sending-paused-notices-fix-button.tsx'; // side effect - renders ReactDOM to document
