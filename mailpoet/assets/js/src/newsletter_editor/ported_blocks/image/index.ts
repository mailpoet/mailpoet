import { registerBlockType } from '@wordpress/blocks';
import { Edit } from './edit';
import metadata from './block.json';
// import { Save } from './save';

registerBlockType(metadata.name, {
  title: 'Image Ported Block',
  example: {
    attributes: {
      message: 'Image Ported Block',
    },
  },
  icon: 'format-image',
  category: 'design',
  attributes: {
    legacyBlockData: {
      type: 'object',
    },
  },
  edit: Edit,
  save: () => null,
});
