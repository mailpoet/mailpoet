import { has } from 'lodash';

const mapCustomField = (block, customFields, mappedCommonProperties) => {
  const customField = customFields.find((cf) => cf.id === block.attributes.customFieldId);
  if (!customField) return null;
  const mapped = {
    ...mappedCommonProperties,
    id: block.attributes.customFieldId.toString(),
    name: customField.name,
    unique: '1',
  };
  if (block.name.startsWith('mailpoet-form/custom-date')) {
    mapped.type = 'date';
  }
  if (block.name.startsWith('mailpoet-form/custom-text')) {
    mapped.type = 'text';
  }
  if (block.name.startsWith('mailpoet-form/custom-textarea')) {
    mapped.type = 'textarea';
  }
  if (block.name.startsWith('mailpoet-form/custom-radio')) {
    mapped.type = 'radio';
  }
  if (block.name.startsWith('mailpoet-form/custom-checkbox')) {
    mapped.type = 'checkbox';
  }
  if (block.name.startsWith('mailpoet-form/custom-select')) {
    mapped.type = 'select';
  }
  if (has(block.attributes, 'validate')) {
    mapped.params.validate = block.attributes.validate;
  }
  if (has(block.attributes, 'hideLabel') && block.attributes.hideLabel) {
    mapped.params.hide_label = '1';
  }
  if (has(block.attributes, 'defaultToday') && block.attributes.defaultToday) {
    mapped.params.is_default_today = '1';
  }
  if (has(block.attributes, 'dateType')) {
    mapped.params.date_type = block.attributes.dateType;
  }
  if (has(block.attributes, 'lines')) {
    mapped.params.lines = block.attributes.lines;
  }
  if (has(block.attributes, 'dateFormat')) {
    mapped.params.date_format = block.attributes.dateFormat;
  }
  if (has(block.attributes, 'values')) {
    mapped.params.values = block.attributes.values.map((value) => {
      const mappedValue = {
        value: value.name,
      };
      if (has(value, 'isChecked') && value.isChecked) {
        mappedValue.is_checked = '1';
      }
      return mappedValue;
    });
  }
  return mapped;
};

/**
 * Transforms blocks to form.body data structure.
 * @param blocks - blocks representation taken from @wordpress/block-editor
 * @param customFields - list of all custom Fields
 * @param parent - parent block of nested block
 */
const mapBlocks = (blocks, customFields = [], parent = null) => {
  if (!Array.isArray(blocks)) {
    throw new Error('Mapper expects blocks to be an array.');
  }
  if (!Array.isArray(customFields)) {
    throw new Error('Mapper expects customFields to be an array.');
  }
  return blocks.map((block, index) => {
    const mapped = {
      type: 'text',
      unique: '0',
      static: '1',
      position: (index + 1).toString(),
      params: {
        label: block.attributes.label,
      },
    };
    if (block.attributes.mandatory) {
      mapped.params.required = '1';
    }
    if (block.attributes.labelWithinInput) {
      mapped.params.label_within = '1';
    }

    switch (block.name) {
      case 'core/column':
        return {
          position: (index + 1).toString(),
          type: 'column',
          params: {
            vertical_alignment: block.attributes.verticalAlignment || null,
            width: block.attributes.width
              ? block.attributes.width : Math.round(100 / parent.innerBlocks.length),
          },
          body: mapBlocks(block.innerBlocks, customFields, block),
        };
      case 'core/columns':
        return {
          position: (index + 1).toString(),
          type: 'columns',
          body: mapBlocks(block.innerBlocks, customFields, block),
          params: {
            text_color: block.attributes.textColor || null,
            background_color: block.attributes.backgroundColor || null,
            custom_text_color: block.attributes.customTextColor || null,
            custom_background_color: block.attributes.customBackgroundColor || null,
          },
        };
      case 'mailpoet-form/email-input':
        return {
          ...mapped,
          id: 'email',
          name: 'Email',
          params: {
            ...mapped.params,
            required: '1',
          },
        };
      case 'mailpoet-form/first-name-input':
        return {
          ...mapped,
          id: 'first_name',
          unique: '1',
          static: '0',
          name: 'First name',
        };
      case 'mailpoet-form/last-name-input':
        return {
          ...mapped,
          id: 'last_name',
          unique: '1',
          static: '0',
          name: 'Last name',
        };
      case 'mailpoet-form/segment-select':
        return {
          ...mapped,
          id: 'segments',
          type: 'segment',
          unique: '1',
          static: '0',
          name: 'List selection',
          params: {
            ...mapped.params,
            values: block.attributes.values.map((segment) => ({
              id: segment.id,
              is_checked: segment.isChecked ? '1' : undefined,
              name: segment.name,
            })),
          },
        };
      case 'mailpoet-form/submit-button':
        return {
          ...mapped,
          id: 'submit',
          type: 'submit',
          name: 'Submit',
        };
      case 'mailpoet-form/divider':
        return {
          ...mapped,
          id: 'divider',
          type: 'divider',
          name: 'Divider',
          static: '0',
          params: '',
        };
      case 'mailpoet-form/html':
        return {
          ...mapped,
          id: 'html',
          type: 'html',
          name: 'Custom text or HTML',
          static: '0',
          params: {
            text: block.attributes && block.attributes.content ? block.attributes.content : '',
            nl2br: block.attributes && block.attributes.nl2br ? '1' : '0',
          },
        };
      default:
        if (block.name.startsWith('mailpoet-form/custom-')) {
          return mapCustomField(block, customFields, mapped);
        }
        return null;
    }
  }).filter(Boolean);
};

export default mapBlocks;
