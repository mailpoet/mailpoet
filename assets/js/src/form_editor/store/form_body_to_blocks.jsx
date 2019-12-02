export default (data) => (
  data.map((item) => {
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
      case 'submit':
        mapped.name = 'mailpoet-form/submit-button';
        mapped.attributes = {
          label: item.params.label,
        };
        return mapped;
      default:
        return null;
    }
  }).filter(Boolean)
);
