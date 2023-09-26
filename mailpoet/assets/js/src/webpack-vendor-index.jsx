// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

import 'handlebars'; // no side effect - this just explicitly requires Handlebars
import 'handlebars_helpers'; // side effect - extends Handlebars, assigns to window
import 'wp-js-hooks'; // side effect - assigns to window
