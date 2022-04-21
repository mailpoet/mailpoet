import { addFilter } from '@wordpress/hooks';
import { name as footerBlockName } from 'newsletter_editor_v2/blocks/footer';
import { name as headerBlockName } from 'newsletter_editor_v2/blocks/header';

export const registerColumn = () => {
  const modifySettings = (settings, name) => {
    if (name !== 'core/column') {
      return settings;
    }
    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        allowedBlocks: {
          type: 'array',
          default: [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/image',
            'core/spacer',
            'core/divider',
            'core/social-link',
            'core/social-links',
            'mailpoet/todo-block',
            'core/button',
            'core/buttons',
            footerBlockName,
            headerBlockName,
          ],
        },
      },
    };
  };

  addFilter(
    'blocks.registerBlockType',
    'mailpeot/column-modifications-register',
    modifySettings,
  );
};
