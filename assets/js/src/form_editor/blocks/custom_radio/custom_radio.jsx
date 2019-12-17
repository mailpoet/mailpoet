import Icon from '../custom_text/icon.jsx';
import Edit from './edit.jsx';

export const name = 'mailpoet-form/custom-radio';

export function getSettings(customField) {
  return {
    title: customField.name,
    description: '',
    icon: Icon,
    category: 'custom-fields',
    attributes: {
      label: {
        type: 'string',
        default: customField.name,
      },
      displayLabel: {
        type: 'boolean',
        default: true,
      },
      mandatory: {
        type: 'boolean',
        default: false,
      },
      values: {
        type: 'array',
        default: [],
      },
      customFieldId: {
        type: 'string',
        default: customField.id,
      },
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
}
