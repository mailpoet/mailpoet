import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks';

function enhanceColumnBlock() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/change-column',
    (settings: Block, name) => {
      if (name === 'core/column') {
        return {
          ...settings,
          supports: {
            ...settings.supports,
            background: {
              backgroundImage: true,
            },
          },
        };
      }
      return settings;
    },
  );
}

export { enhanceColumnBlock };
