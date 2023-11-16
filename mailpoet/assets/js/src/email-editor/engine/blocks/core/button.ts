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

      if (name === 'core/buttons') {
        return {
          ...settings,
          supports: {
            ...settings.supports,
            layout: false, // disable block editor's layouts
            __experimentalEmailFlexLayout: true, // enable MailPoet's reduced flex email layout
          },
        };
      }
      return settings;
    },
  );
}

export { enhanceButtonBlock };
