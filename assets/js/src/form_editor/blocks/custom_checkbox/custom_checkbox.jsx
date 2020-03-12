import Icon from '../custom_text/icon.jsx';
import Edit from './edit.jsx';

export const name = 'mailpoet-form/custom-checkbox';

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
      hideLabel: {
        type: 'boolean',
        default: false,
      },
      values: {
        type: 'array',
        default: [],
      },
      mandatory: {
        type: 'boolean',
        default: customField.params.required ? !!customField.params.required : false,
      },
      customFieldId: {
        type: 'string',
        default: customField.id,
      },
    },
    supports: {
      html: false,
      multiple: false,
    },
    edit: Edit,
    save() {
      return null;
    },
  };
}
