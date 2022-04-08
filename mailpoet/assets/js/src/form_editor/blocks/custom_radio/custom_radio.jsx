import Icon from '../custom_text/icon.jsx';
import Edit from './edit.jsx';
import { customFieldValuesToBlockValues } from '../../store/form_body_to_blocks.jsx';

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
      hideLabel: {
        type: 'boolean',
        default: false,
      },
      mandatory: {
        type: 'boolean',
        default: customField.params.required
          ? !!customField.params.required
          : false,
      },
      values: {
        type: 'array',
        default: customField.params.values
          ? customFieldValuesToBlockValues(customField.params.values)
          : [],
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
