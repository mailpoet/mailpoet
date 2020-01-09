import Icon from './icon.jsx';
import Edit from './edit.jsx';

export const name = 'mailpoet-form/add-custom-field';

export const settings = {
  title: 'Create Custom Field',
  description: 'Create a new custom field for your subscribers.',
  icon: Icon,
  category: 'custom-fields',
  attributes: {
  },
  supports: {
    html: false,
    customClassName: false,
    multiple: false,
  },
  edit: Edit,
  save() {
    return null;
  },
};
