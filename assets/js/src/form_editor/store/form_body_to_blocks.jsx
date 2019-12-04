/**
 * Transforms form body items to array of blocks which can be passed to block editor.
 * @param data - from form.body property
 */
export default (data) => {
  if (!Array.isArray(data)) {
    throw new Error('Mapper expects form body to be an array.');
  }
  return data.map((item) => {
    const mapped = {
      clientId: item.id,
      isValid: true,
      innerBlocks: [],
      attributes: {
        label: item.params.label,
      },
    };
    if (Object.prototype.hasOwnProperty.call(item.params, 'required')) {
      mapped.attributes.mandatory = !!item.params.required;
    }
    if (Object.prototype.hasOwnProperty.call(item.params, 'label_within')) {
      mapped.attributes.labelWithinInput = !!item.params.label_within;
    }
    switch (item.id) {
      case 'email':
        return {
          name: 'mailpoet-form/email-input',
          ...mapped,
        };
      case 'first_name':
        return {
          name: 'mailpoet-form/first-name-input',
          ...mapped,
        };
      case 'last_name':
        return {
          name: 'mailpoet-form/last-name-input',
          ...mapped,
        };
      case 'submit':
        return {
          name: 'mailpoet-form/submit-button',
          ...mapped,
        };
      default:
        return null;
    }
  }).filter(Boolean);
};
