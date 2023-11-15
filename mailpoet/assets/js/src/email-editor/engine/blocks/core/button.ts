import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks';

/**
 * Disables Styles for button
 */
function enhanceButtonBlock() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/change-button',
    (settings: Block, name) => {
      if (name === 'core/button') {
        return { ...settings, styles: [] };
      }
      return settings;
    },
  );
}

export { enhanceButtonBlock };
