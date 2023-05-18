import { registerBlockType } from '@wordpress/blocks';
import { Edit } from './edit';
import metadata from './block.json';
// import { Save } from './save';

registerBlockType(metadata.name, {
  title: 'Button Ported Block',
  example: {
    attributes: {
      message: 'Button Ported Block',
    },
  },
  icon: 'button',
  category: 'design',
  attributes: {
    legacyBlockData: {
      type: 'object',
    },
  },
  edit: Edit,
  save: () => null,
});
