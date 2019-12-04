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
    };
    switch (item.id) {
      case 'email':
        mapped.name = 'mailpoet-form/email-input';
        mapped.attributes = {
          label: item.params.label,
          labelWithinInput: !!item.params.label_within,
        };
        return mapped;
      case 'first_name':
        mapped.name = 'mailpoet-form/first-name-input';
        mapped.attributes = {
          label: item.params.label,
          labelWithinInput: !!item.params.label_within,
          mandatory: !!item.params.required,
        };
        return mapped;
      case 'last_name':
        mapped.name = 'mailpoet-form/last-name-input';
        mapped.attributes = {
          label: item.params.label,
          labelWithinInput: !!item.params.label_within,
          mandatory: !!item.params.required,
        };
        return mapped;
      case 'submit':
        mapped.name = 'mailpoet-form/submit-button';
        mapped.attributes = {
          label: item.params.label,
        };
        return mapped;
      default:
        return null;
    }
  }).filter(Boolean);
};
