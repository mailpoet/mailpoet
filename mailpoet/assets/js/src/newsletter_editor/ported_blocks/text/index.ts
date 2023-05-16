import { registerBlockType } from '@wordpress/blocks';
import { Edit } from './edit';
import { supports, name } from './block.json';
// import { Save } from './save';

registerBlockType(name, {
  title: 'Text Ported Block',
  example: {
    attributes: {
      message: 'Text Ported Block',
    },
  },
  icon: 'format-quote',
  category: 'design',
  attributes: {
    message: {
      type: 'string',
      source: 'text',
      selector: 'div',
      default: 'This is the text',
    },
  },
  supports,
  edit: Edit,
  save: () => null,
});
