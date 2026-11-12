import { getWpCompatOverlaySlot } from '@wordpress/ui';

// WordPress 7.0's Dropdown closes when focus moves into a @wordpress/ui
// overlay, like the operator Select in a DataViews filter, because the
// overlay is rendered outside the popover. Dropdown ignores focus inside other
// dialogs, so we mark the overlay slot as one. Remove once our minimum
// WordPress is 7.1. See https://github.com/WordPress/gutenberg/issues/78406.
function markOverlaySlotAsDialog(): void {
  const isWordPress70 = [...document.body.classList].some((className) =>
    className.startsWith('version-7-0'),
  );
  if (isWordPress70) {
    getWpCompatOverlaySlot()?.setAttribute('role', 'dialog');
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', markOverlaySlotAsDialog);
} else {
  markOverlaySlotAsDialog();
}
