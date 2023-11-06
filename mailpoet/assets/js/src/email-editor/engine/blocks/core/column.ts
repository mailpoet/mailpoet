import { addFilter } from '@wordpress/hooks';

/**
 * Disable nesting columns inside columns by using WP hooks
 */
function disableNestedColumns() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/change-columns-allowed-nesting',
    (settings, name) => {
      if (name === 'core/column') {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        return {
          ...settings,
          attributes: {
            ...settings.attributes,
            allowedBlocks: {
              type: 'array',
              default: ['core/paragraph', 'core/heading'],
            },
          },
        };
      }

      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return settings;
    },
  );
}

export { disableNestedColumns };
