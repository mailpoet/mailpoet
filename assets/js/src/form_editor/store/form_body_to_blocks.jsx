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
      case 'submit':
        return {
          ...mapped,
          name: 'mailpoet-form/submit-button',
        };
      default:
        return null;
    }
  }).filter(Boolean);
};
