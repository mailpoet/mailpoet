import { registerBlockType } from '@wordpress/blocks';
import { Edit } from './edit';
import metadata from './block.json';
// import { Save } from './save';

registerBlockType(metadata.name, {
  title: 'Sample Ported Block',
  example: {
    attributes: {
      message: 'Sample Ported Block',
    },
  },
  icon: 'flag',
  category: 'design',
  attributes: {
    message: {
      type: 'string',
      source: 'text',
      selector: 'div',
      default: 'This is the text',
    },
  },
  edit: Edit,
  save: () => null,
});
