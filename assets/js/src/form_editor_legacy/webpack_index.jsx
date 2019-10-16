// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

import 'form_editor_legacy/form_editor.js'; // side effect - calls document.observe()
import 'codemirror'; // side effect - has to be loaded here, used in 'editor.html'
import 'codemirror/mode/css/css'; // side effect - has to be loaded here, used in 'editor.html'
