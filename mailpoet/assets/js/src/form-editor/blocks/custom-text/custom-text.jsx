import { defaultBlockStyles } from 'form-editor/store/mapping/to-blocks/styles-mapper';
import { Icon } from './icon.jsx';
import { CustomTextEdit } from './edit.jsx';

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
        default: customField.params.required
          ? !!customField.params.required
          : false,
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
      multiple: false,
    },
    edit: CustomTextEdit,
    save() {
      return null;
    },
  };
}
