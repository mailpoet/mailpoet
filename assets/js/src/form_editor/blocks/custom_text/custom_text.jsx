import Icon from './icon.jsx';
import Edit from './edit.jsx';
import { defaultBlockStyles } from '../../store/form_body_to_blocks.jsx';

export const name = 'mailpoet-form/custom-text';

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
        default: customField.params.required ? !!customField.params.required : false,
      },
      validate: {
        type: 'string',
        default: customField.params.validate ? customField.params.validate : '',
      },
      customFieldId: {
        type: 'string',
        default: customField.id,
      },
      styles: {
        type: 'object',
        default: defaultBlockStyles,
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
