
const mapCustomField = (block, customFields, mappedCommonProperties) => {
  const customField = customFields.find((cf) => cf.id === block.attributes.customFieldId);
  if (!customField) return null;
  return {
    ...mappedCommonProperties,
    id: block.attributes.customFieldId.toString(),
    name: customField.name,
    params: {
      ...mappedCommonProperties.params,
      validate: block.attributes.validate,
    },
  };
};

/**
 * Transforms blocks to form.body data structure.
 * @param blocks - blocks representation taken from @wordpress/block-editor
 * @param customFields - list of all custom Fields
 */
export default (blocks, customFields = []) => {
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
