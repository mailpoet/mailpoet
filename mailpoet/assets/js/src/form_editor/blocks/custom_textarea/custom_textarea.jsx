import { defaultBlockStyles } from 'form_editor/store/mapping/to_blocks/styles_mapper';
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
        default: customField.params.required
          ? !!customField.params.required
          : false,
      },
      validate: {
        type: 'string',
        default: customField.params.validate ? customField.params.validate : '',
      },
      lines: {
        type: 'string',
        default: '1',
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
    edit: Edit,
    save() {
      return null;
    },
  };
}
