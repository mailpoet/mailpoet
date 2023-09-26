import { Icon } from '../custom-text/icon.jsx';
import { CustomSelectEdit } from './edit.jsx';
import { customFieldValuesToBlockValues } from '../../store/form-body-to-blocks.jsx';

export const name = 'mailpoet-form/custom-select';

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
    edit: CustomSelectEdit,
    save() {
      return null;
    },
  };
}
