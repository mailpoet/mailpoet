import { has } from 'lodash';

const mapCustomField = (block, customFields, mappedCommonProperties) => {
  const customField = customFields.find((cf) => cf.id === block.attributes.customFieldId);
  if (!customField) return null;
  const mapped = {
    ...mappedCommonProperties,
    id: block.attributes.customFieldId.toString(),
    name: customField.name,
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
 * @param {Array.<{name: string, slug: string, color: string}>} colorDefinitions
 * @param {string} colorSlug
 * @param {string} colorValue
 */
export const mapColorSlugToValue = (colorDefinitions, colorSlug, colorValue = null) => {
  const result = colorDefinitions.find((color) => color.slug === colorSlug);
  return result ? result.color : colorValue;
};

/**
 * Factory for block to form data mapper
 * @param {Array.<{name: string, slug: string, color: string}>} colorDefinitions
 * @param customFields - list of all custom Fields
 */
export const blocksToFormBodyFactory = (colorDefinitions, customFields = []) => {
  /**
   * @param blocks
   * @param parent  - parent block of nested block
   * @returns {*}
   */
  const mapBlocks = (blocks, parent = null) => {
    if (!Array.isArray(blocks)) {
      throw new Error('Mapper expects blocks to be an array.');
    }
    if (!Array.isArray(customFields)) {
      throw new Error('Mapper expects customFields to be an array.');
    }
    return blocks.map((block) => {
      const mapped = {
        type: 'text',
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
      const childrenCount = parent ? parent.innerBlocks.length : 1;
      switch (block.name) {
        case 'core/heading':
          return {
            type: 'heading',
            id: 'heading',
            params: {
              content: block.attributes.content,
              level: block.attributes.level,
              align: block.attributes.align || 'left',
              text_color: block.attributes.textColor || '#000',
              anchor: block.attributes.anchor || null,
              class_name: block.attributes.className || null,
            },
          };
        case 'core/column':
          return {
            type: 'column',
            params: {
              class_name: block.attributes.className || null,
              vertical_alignment: block.attributes.verticalAlignment || null,
              width: block.attributes.width
                ? block.attributes.width : Math.round(100 / childrenCount),
            },
            body: mapBlocks(block.innerBlocks, block),
          };
        case 'core/columns':
          return {
            type: 'columns',
            body: mapBlocks(block.innerBlocks, block),
            params: {
              vertical_alignment: block.attributes.verticalAlignment || null,
              class_name: block.attributes.className || null,
              text_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.textColor,
                block.attributes.customTextColor
              ),
              background_color: mapColorSlugToValue(
                colorDefinitions,
                block.attributes.backgroundColor,
                block.attributes.customBackgroundColor
              ),
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
            name: 'First name',
          };
        case 'mailpoet-form/last-name-input':
          return {
            ...mapped,
            id: 'last_name',
            name: 'Last name',
          };
        case 'mailpoet-form/segment-select':
          return {
            ...mapped,
            id: 'segments',
            type: 'segment',
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
            params: '',
          };
        case 'mailpoet-form/html':
          return {
            ...mapped,
            id: 'html',
            type: 'html',
            name: 'Custom text or HTML',
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
  return mapBlocks;
};
