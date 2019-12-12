import Icon from '../custom_text/icon.jsx';
import Edit from './edit.jsx';

export const name = 'mailpoet-form/custom-textarea';

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
      labelWithinInput: {
        type: 'boolean',
        default: true,
      },
      mandatory: {
        type: 'boolean',
        default: false,
      },
      validate: {
        type: 'string',
        default: '',
      },
      lines: {
        type: 'string',
        default: '1',
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
