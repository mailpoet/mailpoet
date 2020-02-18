import { has } from 'lodash';
import formatCustomFieldBlockName from '../blocks/format_custom_field_block_name.jsx';

const generateId = () => (`${Math.random().toString()}-${Date.now()}`);

export const customFieldValuesToBlockValues = (values) => values.map((value) => {
  const mappedValue = {
    name: value.value,
    id: generateId(),
  };
  if (has(value, 'is_checked') && value.is_checked) {
    mappedValue.isChecked = true;
  }
  return mappedValue;
});

const mapCustomField = (item, customFields, mappedCommonProperties) => {
  const customField = customFields.find((cf) => cf.id === parseInt(item.id, 10));
  if (!customField) return null;

  const namesMap = {
    text: 'mailpoet-form/custom-text',
    textarea: 'mailpoet-form/custom-textarea',
    radio: 'mailpoet-form/custom-radio',
    checkbox: 'mailpoet-form/custom-checkbox',
    select: 'mailpoet-form/custom-select',
    date: 'mailpoet-form/custom-date',
  };
  const mapped = {
    ...mappedCommonProperties,
    name: formatCustomFieldBlockName(namesMap[customField.type], customField),
  };
  mapped.attributes.customFieldId = customField.id;
  if (item.params) {
    if (has(item.params, 'validate') && !!item.params.validate) {
      mapped.attributes.validate = item.params.validate;
    }
    if (has(item.params, 'hide_label')) {
      mapped.attributes.hideLabel = !!item.params.hide_label;
    }
    if (has(item.params, 'lines')) {
      mapped.attributes.lines = item.params.lines;
    }
    if (has(item.params, 'date_type')) {
      mapped.attributes.dateType = item.params.date_type;
    }
    if (has(item.params, 'date_format')) {
      mapped.attributes.dateFormat = item.params.date_format;
    }
    if (has(item.params, 'is_default_today')) {
      mapped.attributes.defaultToday = !!item.params.is_default_today;
    }
    if (has(item.params, 'values') && Array.isArray(item.params.values)) {
      mapped.attributes.values = customFieldValuesToBlockValues(item.params.values);
    }
  }
  return mapped;
};

const mapColumnBlocks = (data, customFields = []) => {
  const mapped = {
    clientId: generateId(),
    name: `core/${data.type}`,
    isValid: true,
    attributes: {},
    // eslint-disable-next-line no-use-before-define
    innerBlocks: formBodyToBlocks(data.body ? data.body : [], customFields),
  };
  if (has(data.params, 'width')) {
    mapped.attributes.width = parseFloat(data.params.width);
  }
  if (has(data.params, 'vertical_alignment')) {
    mapped.attributes.verticalAlignment = data.params.vertical_alignment;
  }
  if (has(data.params, 'text_color')) {
    mapped.attributes.textColor = data.params.text_color;
  }
  if (has(data.params, 'custom_text_color')) {
    mapped.attributes.customTextColor = data.params.custom_text_color;
  }
  if (has(data.params, 'background_color')) {
    mapped.attributes.backgroundColor = data.params.background_color;
  }
  if (has(data.params, 'custom_background_color')) {
    mapped.attributes.customBackgroundColor = data.params.custom_background_color;
  }
  if (has(data.params, 'class_name') && data.params.class_name) {
    mapped.attributes.className = data.params.class_name;
  }
  return mapped;
};

/**
 * Transforms form body items to array of blocks which can be passed to block editor.
 * @param {array} data - from form.body property
 * @param {array} customFields - list of all custom fields
 */
export const formBodyToBlocks = (data, customFields = []) => {
  if (!Array.isArray(data)) {
    throw new Error('Mapper expects form body to be an array.');
  }
  if (!Array.isArray(customFields)) {
    throw new Error('Mapper expects customFields to be an array.');
  }

  return data.map((item, index) => {
    if (['column', 'columns'].includes(item.type)) {
      return mapColumnBlocks(item, customFields);
    }

    const mapped = {
      clientId: `${item.id}_${index}`,
      isValid: true,
      innerBlocks: [],
      attributes: {
        labelWithinInput: false,
        mandatory: false,
      },
    };
    if (item.params && has(item.params, 'required')) {
      mapped.attributes.mandatory = !!item.params.required;
    }
    if (item.params && has(item.params, 'label_within')) {
      mapped.attributes.labelWithinInput = !!item.params.label_within;
    }
    if (item.params) {
      mapped.attributes.label = item.params.label ? item.params.label : '';
    }
    switch (item.id) {
      case 'email':
        return {
          ...mapped,
          name: 'mailpoet-form/email-input',
        };
      case 'first_name':
        return {
          ...mapped,
          name: 'mailpoet-form/first-name-input',
        };
      case 'last_name':
        return {
          ...mapped,
          name: 'mailpoet-form/last-name-input',
        };
      case 'segments':
        if (
          item.params
          && has(item.params, 'values')
          && Array.isArray(item.params.values)
        ) {
          mapped.attributes.values = item.params.values.map((value) => ({
            id: value.id,
            name: value.name,
            isChecked: value.is_checked === '1' ? true : undefined,
          }));
        } else {
          mapped.attributes.values = [];
        }
        return {
          ...mapped,
          name: 'mailpoet-form/segment-select',
        };
      case 'submit':
        return {
          ...mapped,
          name: 'mailpoet-form/submit-button',
        };
      case 'divider':
        delete mapped.attributes.label;
        return {
          ...mapped,
          name: 'mailpoet-form/divider',
        };
      case 'html':
        delete mapped.attributes.label;
        return {
          ...mapped,
          name: 'mailpoet-form/html',
          attributes: {
            content: item.params && item.params.text ? item.params.text : '',
            nl2br: item.params && item.params.nl2br ? !!item.params.nl2br : false,
          },
        };
      default:
        if (Number.isInteger(parseInt(item.id, 10))) {
          return mapCustomField(item, customFields, mapped);
        }
        return null;
    }
  }).filter(Boolean);
};
