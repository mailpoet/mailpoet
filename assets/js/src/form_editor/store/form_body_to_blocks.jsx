import formatCustomFieldBlockName from '../blocks/format_custom_field_block_name.jsx';

const mapCustomField = (item, customFields, mappedCommonProperties) => {
  const customField = customFields.find((cf) => cf.id === parseInt(item.id, 10));
  if (!customField) return null;

  const namesMap = {
    text: 'mailpoet-form/custom-text',
    textarea: 'mailpoet-form/custom-textarea',
  };
  const mapped = {
    ...mappedCommonProperties,
    name: formatCustomFieldBlockName(namesMap[customField.type], customField),
  };
  mapped.attributes.customFieldId = customField.id;
  if (
    item.params
    && Object.prototype.hasOwnProperty.call(item.params, 'validate')
    && !!item.params.validate
  ) {
    mapped.attributes.validate = item.params.validate;
  }
  return mapped;
};

/**
 * Transforms form body items to array of blocks which can be passed to block editor.
 * @param {array} data - from form.body property
 * @param {array} customFields - list of all custom fields
 */
export default (data, customFields = []) => {
  if (!Array.isArray(data)) {
    throw new Error('Mapper expects form body to be an array.');
  }
  if (!Array.isArray(customFields)) {
    throw new Error('Mapper expects customFields to be an array.');
  }
  return data.map((item, index) => {
    const mapped = {
      clientId: `${item.id}_${index}`,
      isValid: true,
      innerBlocks: [],
      attributes: {
        labelWithinInput: false,
        mandatory: false,
      },
    };
    if (item.params && Object.prototype.hasOwnProperty.call(item.params, 'required')) {
      mapped.attributes.mandatory = !!item.params.required;
    }
    if (item.params && Object.prototype.hasOwnProperty.call(item.params, 'label_within')) {
      mapped.attributes.labelWithinInput = !!item.params.label_within;
    }
    if (item.params && Object.prototype.hasOwnProperty.call(item.params, 'label')) {
      mapped.attributes.label = item.params.label;
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
          && Object.prototype.hasOwnProperty.call(item.params, 'values')
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
        return {
          ...mapped,
          name: 'mailpoet-form/divider',
        };
      case 'html':
        return {
          ...mapped,
          name: 'mailpoet-form/custom-html',
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
