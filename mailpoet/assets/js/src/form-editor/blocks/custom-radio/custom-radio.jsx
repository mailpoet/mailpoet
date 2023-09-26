import { Icon } from '../custom-text/icon.jsx';
import { CustomRadioEdit } from './edit.jsx';
import { customFieldValuesToBlockValues } from '../../store/form-body-to-blocks.jsx';

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
    edit: CustomRadioEdit,
    save() {
      return null;
    },
  };
}
