import { Icon } from './icon.jsx';
import { Edit } from './edit.jsx';

const wp = window.wp;
const { registerBlockType } = wp.blocks;

registerBlockType('mailpoet/subscription-form-block-render', {
  title: window.locale.subscriptionForm,
  attributes: {
    formId: {
      type: 'number',
      default: null,
    },
  },
  supports: {
    inserter: false,
  },
});

registerBlockType('mailpoet/subscription-form-block', {
  title: window.locale.subscriptionForm,
  icon: Icon,
  category: 'widgets',
  example: {},
  attributes: {
    formId: {
      type: 'number',
      default: null,
    },
  },
  edit: Edit,
  save() {
    return null;
  },
});
