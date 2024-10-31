import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks';

/**
 * Disables Styles for spacer
 * We don't support margin in the editor
 */
function enhanceSpacerBlock() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/enhance-spacer-block',
    (settings: Block, name) => {
      if (name === 'core/spacer') {
        return {
          ...settings,
          styles: [],
          supports: { ...settings.supports, spacing: {} },
        };
      }
      return settings;
    },
  );
}

export { enhanceSpacerBlock };
