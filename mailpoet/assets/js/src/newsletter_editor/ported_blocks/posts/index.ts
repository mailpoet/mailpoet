import { registerBlockType } from '@wordpress/blocks';
import { Edit } from './edit';
import metadata from './block.json';
// import { Save } from './save';

registerBlockType(metadata.name, {
  title: 'Posts Ported Block',
  example: {
    attributes: {
      message: 'Posts Ported Block',
    },
  },
  icon: 'admin-post',
  category: 'design',
  attributes: {},
  edit: Edit,
  save: () => null,
});
