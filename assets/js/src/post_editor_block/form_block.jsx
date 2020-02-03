import Icon from './icon.jsx';
import Edit from './edit.jsx';

const wp = window.wp;
const { registerBlockType } = wp.blocks;

registerBlockType('mailpoet/form-block', {
  title: 'MailPoet Subscription Form',
  icon: Icon,
  category: 'widgets',
  example: {},
  attributes: {
    selectedForm: {
      type: 'number',
      default: null,
    },
  },
  edit: Edit,
  save() {
    return null;
  },
});
