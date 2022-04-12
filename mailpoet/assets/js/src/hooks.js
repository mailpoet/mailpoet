import { createHooks } from '@wordpress/hooks';

// Make sure the hooks library is available globally
// because it is used in views (e.g. newsletter/editor.html)
window.wp = window.wp || {};
window.wp.hooks = window.wp.hooks || createHooks();

export const Hooks = window.wp.hooks;
