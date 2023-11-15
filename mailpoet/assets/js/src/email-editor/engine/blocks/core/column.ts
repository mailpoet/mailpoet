import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { Block } from '@wordpress/blocks';
import { storeName } from '../../store';

/**
 * Disable nesting columns inside columns by using WP hooks
 */
function disableNestedColumns() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/change-columns-allowed-nesting',
    (settings: Block, name) => {
      if (name === 'core/column') {
        // Filter out core/column and core/columns from supported blocks configured in the editor settings
        const editorSettings = select(storeName).getInitialEditorSettings();
        const blockTypes =
          editorSettings.allowedBlockTypes instanceof Array
            ? editorSettings.allowedBlockTypes
            : [];
        const allowedBlockTypes = [];
        blockTypes.forEach((allowedBlockType) => {
          if (
            allowedBlockType !== 'core/column' &&
            allowedBlockType !== 'core/columns' &&
            allowedBlockType !== 'core/list-item'
          ) {
            allowedBlockTypes.push(allowedBlockType);
          }
        });

        return {
          ...settings,
          attributes: {
            ...settings.attributes,
            allowedBlocks: {
              type: 'array',
              default: allowedBlockTypes,
            },
          },
        };
      }

      return settings;
    },
  );
}

export { disableNestedColumns };
