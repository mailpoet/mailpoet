import Icon from '../custom_text/icon.jsx';
import Edit from './edit.jsx';

export const name = 'mailpoet-form/custom-date';

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
      mandatory: {
        type: 'boolean',
        default: customField.params.required
          ? !!customField.params.required
          : false,
      },
      defaultToday: {
        type: 'boolean',
        default: false,
      },
      dateType: {
        type: 'string',
        default: customField.params.date_type,
      },
      dateFormat: {
        type: 'string',
        default: customField.params.date_format,
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
